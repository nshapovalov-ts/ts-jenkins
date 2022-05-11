<?php

namespace StripeIntegration\Payments\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use StripeIntegration\Payments\Helper;
use Magento\Framework\Validator\Exception;
use StripeIntegration\Payments\Helper\Logger;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\Exception\CouldNotSaveException;

class PaymentMethod extends \Magento\Payment\Model\Method\Adapter
{
    protected $_code                 = "stripe_payments";

    protected $_isInitializeNeeded      = false;
    protected $_canUseForMultishipping  = true;

    /**
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param StripeIntegration\Payments\Model\Config $config
     * @param CommandPoolInterface $commandPool
     * @param ValidatorPoolInterface $validatorPool
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Payment\Gateway\Config\ValueHandlerPoolInterface $valueHandlerPool,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\Method\Checkout\Card $checkoutCardMethod,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\Api $api,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\App\CacheInterface $cache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Payment\Gateway\Command\CommandPoolInterface $commandPool = null,
        \Magento\Payment\Gateway\Validator\ValidatorPoolInterface $validatorPool = null
    ) {
        $this->config = $config;
        $this->checkoutCardMethod = $checkoutCardMethod;
        $this->psrLogger = $logger;
        $this->helper = $helper;
        $this->api = $api;
        $this->customer = $helper->getCustomerModel();
        $this->paymentIntent = $paymentIntent;
        $this->checkoutHelper = $checkoutHelper;
        $this->cache = $cache;

        $this->saveCards = $config->getSaveCards();
        $this->evtManager = $eventManager;

        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool
        );
    }

    protected function resetPaymentData()
    {
        $info = $this->getInfoInstance();

        // Reset a previously initialized 3D Secure session
        $info->setAdditionalInformation('stripejs_token', null)
            ->setAdditionalInformation('save_card', null)
            ->setAdditionalInformation('token', null)
            ->setAdditionalInformation("is_recurring_subscription", null)
            ->setAdditionalInformation("is_migrated_subscription", null)
            ->setAdditionalInformation("subscription_customer", null)
            ->setAdditionalInformation("subscription_start", null)
            ->setAdditionalInformation("remove_initial_fee", null)
            ->setAdditionalInformation("off_session", null)
            ->setAdditionalInformation("use_store_currency", null)
            ->setAdditionalInformation("selected_plan", null);
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if ($this->config->getIsStripeAPIKeyError())
            $this->helper->dieWithError("Invalid API key provided");

        // From Magento 2.0.7 onwards, the data is passed in a different property
        $additionalData = $data->getAdditionalData();
        if (is_array($additionalData))
            $data->setData(array_merge($data->getData(), $additionalData));

        $info = $this->getInfoInstance();
        $session = $this->checkoutHelper->getCheckout();

        $this->evtManager->dispatch(
            'stripe_payments_assigndata',
            array(
                'method' => $this,
                'info' => $info,
                'data' => $data
            )
        );

        // If using a saved card
        if (!empty($data['cc_saved']) && $data['cc_saved'] != 'new_card')
        {
            $card = explode(':', $data['cc_saved']);

            $this->resetPaymentData();
            $info->setAdditionalInformation('use_store_currency', $this->config->useStoreCurrency());
            $info->setAdditionalInformation('token', $card[0]);
            $info->setAdditionalInformation('save_card', $data['cc_save']);

            if ($data['selected_plan'])
                $info->setAdditionalInformation('selected_plan', $data['selected_plan']);

            $this->helper->updateBillingAddress($card[0]);

            return $this;
        }

        // Scenarios by OSC modules trying to prematurely save payment details
        if (empty($data['cc_stripejs_token']))
            return $this;

        $card = explode(':', $data['cc_stripejs_token']);
        $data['cc_stripejs_token'] = $card[0]; // To be used by Stripe Subscriptions

        // Security check: If Stripe Elements is enabled, only accept source tokens and saved cards
        if (!$this->helper->isValidToken($card[0]))
            $this->helper->dieWithError("Sorry, we could not perform a card security check. Please contact us to complete your purchase.");

        $this->resetPaymentData();
        $token = $card[0];
        $info->setAdditionalInformation('use_store_currency', $this->config->useStoreCurrency());
        $info->setAdditionalInformation('stripejs_token', $token);
        $info->setAdditionalInformation('save_card', $data['cc_save']);
        $info->setAdditionalInformation('token', $token);

        if ($data['selected_plan'])
            $info->setAdditionalInformation('selected_plan', $data['selected_plan']);

        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        if ($amount > 0)
        {
            $this->paymentIntent->confirmAndAssociateWithOrder($payment->getOrder(), $payment);
        }

        return $this;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        if ($amount > 0)
        {
            // We get in here when the store is configured in Authorize Only mode and we are capturing a payment from the admin
            $token = $payment->getTransactionId();
            if (empty($token))
                $token = $payment->getLastTransId(); // In case where the transaction was not created during the checkout, i.e. with a Stripe Webhook redirect

            if ($token)
            {
                // Capture an authorized payment from the admin area
                $this->helper->capture($token, $payment, $amount, $this->config->retryWithSavedCard());
            }
            else
            {
                // Create a new payment
                $this->paymentIntent->confirmAndAssociateWithOrder($payment->getOrder(), $payment);
            }
        }

        return $this;
    }

    public function cancel(InfoInterface $payment, $amount = null)
    {
        $method = $payment->getMethod();
        $useStoreCurrency = $payment->getAdditionalInformation("use_store_currency");

        if ($method == "stripe_payments" && !$useStoreCurrency)
        {
            // Authorized Only
            $amount = (empty($amount)) ? $payment->getOrder()->getBaseTotalDue() : $amount;
            $currency = $payment->getOrder()->getBaseCurrencyCode();
        }
        else
        {
            // Captured
            $creditmemo = $payment->getCreditmemo();
            if (!empty($creditmemo))
            {
                $rate = $creditmemo->getBaseToOrderRate();
                if (!empty($rate) && is_numeric($rate) && $rate > 0)
                {
                    $amount = round($amount * $rate, 2);
                    $diff = $amount - $payment->getAmountPaid();
                    if ($diff > 0 && $diff <= 1) // Solves a currency conversion rounding issue (Magento rounds .5 down)
                        $amount = $payment->getAmountPaid();
                }
            }

            // Authorized
            $amount = (empty($amount)) ? $payment->getOrder()->getTotalDue() : $amount;
            $currency = $payment->getOrder()->getOrderCurrencyCode();
        }

        $transactionId = $payment->getParentTransactionId();

        // With asynchronous payment methods, the parent transaction may be empty
        if (empty($transactionId))
            $transactionId = $payment->getLastTransId();

        // Case where an invoice is in Pending status, with no transaction ID, receiving a source.failed event which cancels the invoice.
        if (empty($transactionId))
            return $this;

        $transactionId = preg_replace('/-.*$/', '', $transactionId);

        try {
            $cents = 100;
            if ($this->helper->isZeroDecimal($currency))
                $cents = 1;

            $params = array();
            if ($amount > 0)
                $params["amount"] = round($amount * $cents);
            else
                return;

            if (strpos($transactionId, 'pi_') === 0)
            {
                $pi = \Stripe\PaymentIntent::retrieve($transactionId);
                if ($pi->status == \StripeIntegration\Payments\Model\PaymentIntent::AUTHORIZED)
                {
                    $pi->cancel();
                    return $this;
                }
                else
                    $charge = $pi->charges->data[0];
            }
            else
            {
                $charge = $this->api->retrieveCharge($transactionId);
            }

            $params["charge"] = $charge->id;

            // This is true when an authorization has expired or when there was a refund through the Stripe account
            if (!$charge->refunded)
            {
                $this->cache->save($value = "1", $key = "admin_refunded_" . $charge->id, ["stripe_payments"], $lifetime = 60 * 60);
                \Stripe\Refund::create($params);

                $refundId = $this->helper->getRefundIdFrom($charge);
                $payment->setAdditionalInformation('last_refund_id', $refundId);
            }
            else
            {
                $comment = __('An attempt to manually refund the order was made, however this order was already refunded in Stripe. Creating an offline refund instead.');
                $payment->getOrder()->addStatusToHistory($status = false, $comment, $isCustomerNotified = false);
            }
        }
        catch (\Exception $e)
        {
            $this->psrLogger->addError('Could not refund payment: '.$e->getMessage());
            throw new \Exception(__($e->getMessage()));
        }

        return $this;
    }

    public function cancelInvoice($invoice)
    {
        return $this;
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $this->cancel($payment, $amount);

        return $this;
    }

    public function void(InfoInterface $payment)
    {
        $this->cancel($payment);

        return $this;
    }

    public function acceptPayment(InfoInterface $payment)
    {
        return parent::acceptPayment($payment);
    }

    public function denyPayment(InfoInterface $payment)
    {
        return parent::denyPayment($payment);
    }

    public function canCapture()
    {
        return parent::canCapture();
    }

    public function isApplePay()
    {
        $info = $this->getInfoInstance();
        if ($info)
            return $info->getAdditionalInformation("is_prapi");

        return false;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->helper->isRecurringOrder($this))
            return true;

        if (!$this->config->isEnabled())
            return false;

        if ($this->checkoutCardMethod->isAvailable($quote) && !$this->isApplePay())
            return false;

        return parent::isAvailable($quote);
    }

    // The reasoning for overwriting the payment action is that subscription invoices should not be generated at order time
    // instead they should be generated upon an invoice.payment_succeeded webhook arrival
    public function getConfigPaymentAction()
    {
        $action = parent::getConfigPaymentAction();
        if ($action == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE || $this->helper->hasSubscriptions())
            return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;

        return $action;
    }

    // Fixes https://github.com/magento/magento2/issues/5413 in Magento 2.1
    public function setId($code) { }
    public function getId() { return $this->_code; }
}
