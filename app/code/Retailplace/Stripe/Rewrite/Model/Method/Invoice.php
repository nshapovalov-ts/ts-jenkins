<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Rewrite\Model\Method;

use Exception;
use StripeIntegration\Payments\Model\Method\Invoice as MethodInvoice;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use StripeIntegration\Payments\Model\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Framework\Event\ManagerInterface;
use StripeIntegration\Payments\Helper\Generic;
use Psr\Log\LoggerInterface;
use StripeIntegration\Payments\Model\Stripe\ProductFactory;
use StripeIntegration\Payments\Model\Stripe\PriceFactory;
use StripeIntegration\Payments\Model\Stripe\CouponFactory;
use StripeIntegration\Payments\Model\Stripe\InvoiceItemFactory;
use StripeIntegration\Payments\Model\Stripe\InvoiceFactory as StripeInvoiceFactory;
use StripeIntegration\Payments\Model\InvoiceFactory;
use StripeIntegration\Payments\Model\PaymentIntent;
use Magento\Framework\App\CacheInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Stripe\PaymentMethod;
use Stripe\Exception\CardException;
use StripeIntegration\Payments\Model\StripeCustomer;
use Stripe\Exception\ApiErrorException;
use StripeIntegration\Payments\Model\Stripe\Invoice as StripeInvoice;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as MagentoInvoice;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Creditmemo;
use Retailplace\Stripe\Model\Processing;

/**
 * Class Invoice
 */
class Invoice extends MethodInvoice
{

    /**
     * @type string
     */
    const METHOD_CODE = 'stripe_payments_invoice';

    /**
     * @type int
     */
    const STRIPE_INVOICE_PAID = 1;

    /**
     * @type int
     */
    const STRIPE_INVOICE_NOT_PAID = 0;

    /**
     * @type int
     */
    const STRIPE_INVOICE_NOT_PAID_ERROR = 2;

    /**
     * @type int
     */
    const STRIPE_INVOICE_PAID_NOT_APPLICABLE = 10;

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $type = 'invoice';

    /**
     * @var string
     */
    protected $_formBlockType = 'StripeIntegration\Payments\Block\Method\Invoice';

    /**
     * @var string
     */
    protected $_infoBlockType = 'StripeIntegration\Payments\Block\PaymentInfo\Invoice';

    /**
     * @var
     */
    private $evtManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Generic
     */
    private $helper;

    /**
     * @var DataObject|mixed|StripeCustomer|null
     */
    private $customer;

    /**
     * @var int
     */
    private $daysDue;

    /**
     * @var InvoiceItemFactory
     */
    private $invoiceItemFactory;

    /**
     * @var StripeInvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var InvoiceFactory
     */
    private $orderInvoiceFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var CouponFactory
     */
    private $couponFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PaymentIntent
     */
    private $paymentIntent;

    /**
     * @var Processing
     */
    private $processing;

    /**
     * Invoice constructor.
     *
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param $code
     * @param $formBlockType
     * @param $infoBlockType
     * @param Config $config
     * @param Generic $helper
     * @param ProductFactory $productFactory
     * @param PriceFactory $priceFactory
     * @param CouponFactory $couponFactory
     * @param InvoiceItemFactory $invoiceItemFactory
     * @param StripeInvoiceFactory $invoiceFactory
     * @param InvoiceFactory $orderInvoiceFactory
     * @param CacheInterface $cache
     * @param Json $serializer
     * @param LoggerInterface $logger
     * @param PaymentIntent $paymentIntent
     * @param Processing $processing
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        Config $config,
        Generic $helper,
        ProductFactory $productFactory,
        PriceFactory $priceFactory,
        CouponFactory $couponFactory,
        InvoiceItemFactory $invoiceItemFactory,
        StripeInvoiceFactory $invoiceFactory,
        InvoiceFactory $orderInvoiceFactory,
        CacheInterface $cache,
        Json $serializer,
        LoggerInterface $logger,
        PaymentIntent $paymentIntent,
        Processing $processing,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null
    ) {
        $this->customer = $helper->getCustomerModel();
        $this->daysDue = $config->getInvoicingDaysDue();
        $this->config = $config;
        $this->helper = $helper;
        $this->evtManager = $eventManager;
        $this->couponFactory = $couponFactory;
        $this->productFactory = $productFactory;
        $this->invoiceItemFactory = $invoiceItemFactory;
        $this->invoiceFactory = $invoiceFactory;
        $this->orderInvoiceFactory = $orderInvoiceFactory;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->paymentIntent = $paymentIntent;
        $this->processing = $processing;

        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $config,
            $helper,
            $productFactory,
            $priceFactory,
            $couponFactory,
            $invoiceItemFactory,
            $invoiceFactory,
            $orderInvoiceFactory,
            $cache,
            $commandPool,
            $validatorPool
        );
    }

    /**
     * Add calculated date to title on checkout
     *
     * @return string
     */
    public function getTitle(): string
    {
        return sprintf('%1s (Charged on %2s)', $this->getShortTitle(), $this->config->getPaymentDate());
    }

    /**
     * Add title without payment date
     *
     * @return string
     */
    public function getShortTitle(): string
    {
        return str_replace(
            '%days%',
            (string) $this->daysDue,
            parent::getTitle()
        );
    }

    /**
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        if ($this->config->getIsStripeAPIKeyError()) {
            $this->helper->dieWithError("Invalid API key provided");
        }

        // From Magento 2.0.7 onwards, the data is passed in a different property
        $additionalData = $data->getAdditionalData();
        if (is_array($additionalData)) {
            $data->setData(array_merge($data->getData(), $additionalData));
        }

        $info = $this->getInfoInstance();

        $daysDue = $this->daysDue;
        $daysDue = max(0, $daysDue);
        $daysDue = min(999, $daysDue);
        $info->setAdditionalInformation('days_due', $daysDue);
        $info->setAdditionalInformation('confirm', true);
        $info->setAdditionalInformation('payment_settings', [
            'payment_method_types' => ['card', 'au_becs_debit']
        ]);

        $this->evtManager->dispatch(
            'stripe_payments_assigndata',
            array(
                'method' => $this,
                'info'   => $info,
                'data'   => $data
            )
        );

        $this->checkAndChangeBillingPhoneNumber();

        // If using a saved card
        if (!empty($data['cc_saved']) && $data['cc_saved'] != 'new_card') {
            $card = explode(':', $data['cc_saved']);

            $this->resetPaymentData();
            $token = $card[0];
            $info->setAdditionalInformation('use_store_currency', $this->config->useStoreCurrency());
            $info->setAdditionalInformation('token', $token);
            $info->setAdditionalInformation('save_card', $data['cc_save']);
            $info->setAdditionalInformation('off_session', true);
            $this->helper->updateBillingAddress($token);

            return $this;
        }

        // Scenarios by OSC modules trying to prematurely save payment details
        if (empty($data['cc_stripejs_token'])) {
            return $this;
        }

        $card = explode(':', $data['cc_stripejs_token']);
        $data['cc_stripejs_token'] = $card[0]; // To be used by Stripe Subscriptions

        // Security check: If Stripe Elements is enabled, only accept source tokens and saved cards
        if (!$this->helper->isValidToken($card[0])) {
            $this->helper->dieWithError(
                "Sorry, we could not perform a card security check. Please contact us to complete your purchase."
            );
        }

        $this->resetPaymentData();
        $token = $card[0];
        $info->setAdditionalInformation('use_store_currency', $this->config->useStoreCurrency());
        $info->setAdditionalInformation('stripejs_token', $token);
        $info->setAdditionalInformation('save_card', $data['cc_save']);
        $info->setAdditionalInformation('token', $token);
        $info->setAdditionalInformation('off_session', true);

        return $this;
    }

    /**
     * Capture
     *
     * @throws LocalizedException
     * @throws ApiErrorException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if ($amount > 0) {
            if ($payment->getAdditionalInformation('invoice_id')) {
                throw new LocalizedException(__(
                    "This order cannot be captured from Magento. The invoice will be automatically "
                    . "updated once the customer has paid through a Stripe hosted invoice page."
                ));
            }

            $info = $this->getInfoInstance();
            /** @var Order $order */
            $order = $info->getOrder();
            $this->customer->createStripeCustomerIfNotExists(false, $order);
            $customerId = $this->customer->getStripeId();

            $paymentMethod = PaymentMethod::retrieve($order->getPayment()->getAdditionalInformation('token'));

            if ($this->checkCreditCardForDuplicates($order, $paymentMethod)) {
                $this->helper->dieWithError($this->config->getFailMessageForDuplicateCreditCard());
            }

            $this->attachCustomerToPaymentMethod(
                $this->customer,
                $paymentMethod
            );

            //payment card verification
            if (!$this->paymentIntent->paymentCardVerification($order, $payment, $this->customer)) {
                $this->helper->dieWithError("Charge is declined due to authorization process failure, please try the other payment methods");
            }

            $this->customer->updateFromOrder($order);
            $invoice = $this->createInvoice($order, $customerId)->finalize();
            $payment->setAdditionalInformation('invoice_id', $invoice->getId());
            $payment->setLastTransId($invoice->getId());
            $payment->setIsTransactionPending(false);

            if ($paymentMethod) {
                $card = $paymentMethod->card;
                $payment->setCcExpMonth($card->exp_month);
                $payment->setCcExpYear($card->exp_year);
                $payment->setCcLast4($card->last4);
                $payment->setCcType($card->brand);
            }

            $this->config->getStripeClient()->invoices->sendInvoice($invoice->getId(), []);

            $magentoInvoices = $order->getInvoiceCollection()->getItems();
            /** @var MagentoInvoice $magentoInvoice */
            foreach ($magentoInvoices as $magentoInvoice) {
                $magentoInvoice->setStripeInvoiceId($invoice->getId());
                $magentoInvoice->setStripeInvoicePaid(self::STRIPE_INVOICE_NOT_PAID);
                $magentoInvoice->setTransactionId($invoice->getId());
            }

            $order->setCanSendNewEmailFlag(true);
            $order->setPaymentDate($this->config->getPaymentDate());
            $order->addStatusToHistory(Order::STATE_PROCESSING, __("Net %1", $this->daysDue), false);
        }

        return $this;
    }

    /**
     * Reset Payment Data
     *
     * @throws LocalizedException
     */
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

    /**
     * Attach Customer To Payment Method
     *
     * @param $customer
     * @param PaymentMethod $paymentMethod
     * @return bool
     * @throws ApiErrorException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function attachCustomerToPaymentMethod($customer, PaymentMethod $paymentMethod): bool
    {
        try {
            if (!empty($paymentMethod->customer)) {
                if ($paymentMethod->customer != $customer->getStripeId()) {
                    $this->helper->dieWithError("Error: This card belongs to a different customer.");
                }
            } else {
                $paymentMethod->attach(['customer' => $customer->getStripeId()]);
            }
            return true;
        } catch (CardException $e) {
            $this->helper->dieWithError($e->getMessage());
        }

        return false;
    }

    /**
     * Create Invoice
     *
     * @param Order $order
     * @param string $customerId
     * @return StripeInvoice
     * @throws LocalizedException|Exception
     */
    public function createInvoice($order, $customerId): StripeInvoice
    {
        $items = $order->getAllItems();

        if (empty($items)) {
            throw new Exception("Could not create Stripe invoice because the order contains no items.");
        }

        foreach ($items as $item) {
            if (!in_array($item->getProductType(), ["simple", "virtual", "downloadable"])) {
                continue;
            }

            $this->productFactory->create()->fromOrderItem($item);
            $this->invoiceItemFactory->create()->fromOrderItem($item, $order, $customerId);
        }

        $this->invoiceItemFactory->create()->fromShipping($order, $customerId);

        $coupons = [];
        $invoice = $this->invoiceFactory->create()->fromOrderItem($item, $order, $customerId, $coupons);
        if ($invoice->getId()) {
            $this->orderInvoiceFactory->create()
                ->setInvoiceId($invoice->getId())
                ->setOrderIncrementId($order->getIncrementId())
                ->save();
        }

        return $invoice;
    }

    /**
     * Refund using credit notes
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|\Magento\Payment\Model\Method\Adapter|Invoice|MethodInvoice
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws Exception
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();
        $transactionId = $payment->getLastTransId();

        if ($amount <= 0) {
            return $this;
        }

        try {
            $stripeInvoice = \Stripe\Invoice::retrieve($transactionId);
            $invoiceItems = $this->getInvoiceItems($transactionId);

            $params = [];
            $params['invoice'] = $stripeInvoice->id;

            $lines = [];

            if ($creditmemo->getShippingAmount() > 0) {
                $taxRates = [];
                $shippingDescription = __("Shipping")->render();
                foreach ($invoiceItems as $invoiceItem) {
                    if ($invoiceItem->description == $shippingDescription) {
                        $taxRates = $invoiceItem->tax_rates;
                        break;
                    }
                }

                $lines[] = [
                    'type'        => 'custom_line_item',
                    'description' => 'Shipping',
                    'quantity'    => 1,
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount(
                        $creditmemo->getShippingInclTax(),
                        $creditmemo->getOrderCurrencyCode()
                    ),
                    'tax_rates'   => $taxRates
                ];
            }

            /** @var CreditmemoItem $item */
            foreach ($creditmemo->getItems() as $item) {
                $orderItem = $item->getOrderItem();
                if (!in_array($orderItem->getProductType(), ["simple", "virtual", "downloadable"])) {
                    continue;
                }

                $creditMemoItemWithTotal = $this->getCreditmemoItemWithTotal($item);
                if (!$creditMemoItemWithTotal) {
                    continue;
                }

                foreach ($invoiceItems as $invoiceItem) {
                    if ($item->getProductId() == $invoiceItem->price->product) {
                        $lines[] = [
                            'type'        => 'custom_line_item',
                            'description' => $invoiceItem->description . ' (qty: ' . $item->getQty() . ')',
                            'quantity'    => 1,
                            'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($creditMemoItemWithTotal->getRowTotalInclTax(), $creditmemo->getOrderCurrencyCode()),
                            'tax_rates'   => $invoiceItem->tax_rates
                        ];
                    }
                }
            }
            if ($stripeInvoice->amount_paid) {
                $params['refund_amount'] = $this->helper->convertMagentoAmountToStripeAmount($amount, $creditmemo->getOrderCurrencyCode());
            }
            $params['lines'] = $lines;
            $creditNote = $this->config->getStripeClient()->creditNotes->create($params);
            $payment->setAdditionalInformation('last_credit_note_id', $creditNote->id);
        } catch (Exception $e) {
            $this->helper->dieWithError('Could not refund payment: ' . $e->getMessage());
            throw new Exception((string) __($e->getMessage()));
        }

        return $this;
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws ApiErrorException
     */
    private function getInvoiceItems(string $invoiceId): array
    {
        $allItems = [];
        $lastInvoiceItem = null;
        $pageSize = 100;
        $getMore = true;

        while ($getMore) {
            $params = [
                'invoice' => $invoiceId,
                'limit'   => $pageSize,
            ];
            if ($lastInvoiceItem) {
                $params['starting_after'] = $lastInvoiceItem;
            }

            $stripeInvoiceItems = \Stripe\InvoiceItem::all($params);
            foreach ($stripeInvoiceItems as $item) {
                $allItems[] = $item;
                $lastInvoiceItem = $item->id;
            }

            $getMore = $stripeInvoiceItems->has_more;
        }

        return $allItems;
    }

    /**
     * @param CreditmemoItem $item
     * @return CreditmemoItem|null
     */
    private function getCreditmemoItemWithTotal(CreditmemoItem $item): ?CreditmemoItem
    {
        if ($item->getRowTotalInclTax() > 0) {
            return $item;
        }

        $orderItem = $item->getOrderItem();
        $parentOrderItem = $orderItem->getParentItem();

        if (!empty($parentOrderItem)) {
            $creditmemo = $item->getCreditmemo();
            foreach ($creditmemo->getItems() as $itemToCompare) {
                if ($itemToCompare->getOrderItemId() == $parentOrderItem->getItemId() &&
                    $itemToCompare->getRowTotalInclTax() > 0) {
                    return $itemToCompare;
                }
            }
        }

        return null;
    }

    /**
     * Check And Change Billing Phone Number
     */
    public function checkAndChangeBillingPhoneNumber()
    {
        $quote = $this->helper->getQuote();
        $customer = $this->helper->getMagentoCustomer();
        $phone = $customer->getPhoneNumber();
        $billingAddressTelephone = $quote->getBillingAddress()->getTelephone();
        if (!empty($phone) && $phone != $billingAddressTelephone) {
            $quote->getBillingAddress()->setTelephone($phone);
        }
    }

    /**
     * Check Credit Card For Duplicates
     *
     * @param Order $order
     * @param PaymentMethod|null $payment
     * @return bool
     */
    public function checkCreditCardForDuplicates(Order $order, ?PaymentMethod $payment): bool
    {
        $customer = $this->helper->getMagentoCustomer();
        $isAllowPayments = $customer->getIsAllowPayments();
        if ($isAllowPayments) {
            return false;
        }

        $card = $payment->card;
        $expMonth = (string)$card->exp_month;
        $expYear = (string)$card->exp_year;
        $last4 = (string)$card->last4;
        $billingAddress = $order->getBillingAddress();
        $phone = $billingAddress->getTelephone();
        $postcode = $billingAddress->getPostcode();

        return $this->processing->checkCreditCardForDuplicates(
            $postcode,
            $phone,
            $expMonth,
            $expYear,
            $last4,
            [$customer->getId()]
        );
    }
}
