<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Rewrite\Model;

use StripeIntegration\Payments\Model\PaymentIntent as ModelPaymentIntent;
use Exception;
use Stripe\Exception\ApiErrorException;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\DataObject;
use StripeIntegration\Payments\Model\StripeCustomer;

/**
 * Class PaymentIntent
 */
class PaymentIntent extends ModelPaymentIntent
{
    /**
     * @var string
     */
    private $cachePrefix = 'payment_intent_';

    /**
     * @var string[]
     */
    private $tags = ['stripe_payments_payment_intents'];

    /**
     * Payment Card Verification
     *
     * @param Order $order
     * @param InfoInterface $payment
     * @param StripeCustomer $customer
     * @param bool $isUse3ds
     * @return bool
     * @throws Exception
     */
    public function paymentCardVerification(
        Order          $order,
        InfoInterface  $payment,
        StripeCustomer $customer,
        bool           $isUse3ds = false
    ): bool {
        if (!$this->config->isPaymentCardVerificationEnabled()) {
            return true;
        }

        if ($this->config->useStoreCurrency($order)) {
            $currency = $order->getOrderCurrencyCode();
        } else {
            $currency = $order->getBaseCurrencyCode();
        }

        $amount = $order->getGrandTotal();
        $cents = $this->helper->isZeroDecimal($currency) ? 1 : 100;

        $params['amount'] = round($amount * $cents);
        $params['currency'] = strtolower($currency);
        $params['capture_method'] = ModelPaymentIntent::CAPTURE_METHOD_MANUAL;
        $params["payment_method_types"] = ["card"];
        $params['confirmation_method'] = 'manual';
        $params['description'] = $this->config->getPaymentCardVerificationDescription();
        $params['statement_descriptor_suffix'] = $this->config->getPaymentCardVerificationSuffix();
        $params['customer'] = $customer->getStripeId();
        if ($isUse3ds) {
            $params["payment_method_options"]["card"]["request_three_d_secure"] = 'any';
        }

        $quote = $order->getQuote();
        if (empty($quote) || !is_numeric($quote->getGrandTotal())) {
            $this->quote = $quote = $this->getQuote($order->getQuoteId());
        }

        $this->loadFromCache($params, $quote, $order, true);
        if (!$this->paymentIntent) {
            $this->paymentIntent = \Stripe\PaymentIntent::create($params);
            $this->updateCache($quote->getId());
        }

        if ($this->paymentIntent->status == ModelPaymentIntent::CANCELED) {
            return true;
        }

        $confirmParams = [
            "payment_method" => $payment->getAdditionalInformation("token")
        ];

        $piSecrets = [];
        $triggerAuthentication = false;
        try {
            if (!$this->isSuccessfulStatus()) {
                if (!empty($this->paymentIntent->setup_future_usage) && $this->paymentIntent->setup_future_usage) {
                    $this->deleteSavedCard($payment->getAdditionalInformation("token"));
                }

                try {
                    $this->paymentIntent->confirm($confirmParams);
                } catch (\Exception $e) {
                    $this->prepareRollback();
                    $this->helper->maskException($e);
                }

                if ($this->requiresAction()) {
                    $piSecrets[] = $this->getClientSecret();
                }
            }

            $triggerAuthentication = count($piSecrets) > 0;

            if (!$triggerAuthentication && $this->isSuccessfulStatus()) {
                $this->paymentIntent->cancel();
                return true;
            }
        } catch (Exception $e) {
            $this->prepareRollback();
            $this->deleteSavedCard($payment->getAdditionalInformation("token"));
        }

        if ($triggerAuthentication) { // Front-end checkout case, this will trigger the 3DS modal.
            throw new \Exception("Authentication Required: " . implode(",", $piSecrets));
        }

        return false;
    }

    /**
     * Update Cache
     *
     * @param $quoteId
     * @return void
     */
    protected function updateCache($quoteId)
    {
        $key = $this->cachePrefix . $quoteId;
        $data = $this->paymentIntent->id;

        if ($this->helper->isAPIRequest()) {
            $lifetime = 12 * 60 * 60; // 12 hours
            $this->cache->save($data, $key, $this->tags, $lifetime);
        } else {
            $this->session->setData($key, $data);
        }

        $this->paymentIntentsCache[$this->paymentIntent->id] = $this->paymentIntent;
    }

    /**
     * Load From Cache
     * If we already created any payment intents for this quote, load them
     *
     * @param $params
     * @param $quote
     * @param $order
     * @param bool $skipValidation
     * @return \Stripe\PaymentIntent|null
     * @throws ApiErrorException
     */
    public function loadFromCache($params, $quote, $order, bool $skipValidation = false): ?\Stripe\PaymentIntent
    {
        if (empty($quote)) {
            return null;
        }

        $quoteId = $quote->getId();

        if (empty($quoteId)) {
            $quoteId = $quote->getQuoteId(); // Admin order quotes
        }

        if (empty($quoteId)) {
            return null;
        }

        $key = $this->cachePrefix . $quoteId;
        if ($this->helper->isAPIRequest()) {
            $paymentIntentId = $this->cache->load($key);
        } else {
            $paymentIntentId = $this->session->getData($key);
        }

        if (!empty($paymentIntentId) && strpos($paymentIntentId, "pi_") === 0) {
            if (isset($this->paymentIntentsCache[$paymentIntentId]) && $this->paymentIntentsCache[$paymentIntentId] instanceof \Stripe\PaymentIntent) {
                $this->paymentIntent = $this->paymentIntentsCache[$paymentIntentId];
            } else {
                $this->paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                $this->updateCache($quoteId);
            }
        } else {
            return null;
        }

        if (!$skipValidation && ($this->isInvalid($params, $quote, $order) || $this->hasPaymentActionChanged())) {
            $this->destroy($quoteId, true);
            return null;
        }

        return $this->paymentIntent;
    }
}
