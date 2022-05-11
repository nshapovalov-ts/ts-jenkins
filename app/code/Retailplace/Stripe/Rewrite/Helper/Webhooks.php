<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Helper;

use StripeIntegration\Payments\Helper\Webhooks as HelperWebhooks;
use StripeIntegration\Payments\Exception\WebhookException;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice as StripeInvoice;

/**
 * Class Webhooks
 */
class Webhooks extends HelperWebhooks
{
    /**
     * @throws WebhookException
     */
    public function dispatchEvent()
    {
        try {
            if ($this->request->getMethod() == 'GET') {
                throw new WebhookException("Webhooks are working correctly!", 200);
            }

            // Retrieve the request's body and parse it as JSON
            $body = $this->request->getContent();

            $event = json_decode($body, true);
            $stdEvent = json_decode($body);

            if (empty($event['type'])) {
                throw new WebhookException(__("Unknown event type"));
            }

            if ($event['type'] == "product.created") {
                $this->onProductCreated($event, $stdEvent);
                $this->log("200 OK");
                return;
            }

            if (!empty($event['data']['object']['statement_descriptor_suffix'])) {
                $descriptor = $event['data']['object']['statement_descriptor_suffix'];
                $PCVDescriptor = $this->config->getPaymentCardVerificationSuffix();
                if ($descriptor == $PCVDescriptor) {
                    $this->log("200 OK");
                    return;
                }
            }

            $eventType = "stripe_payments_webhook_" . str_replace(".", "_", $event['type']);

            if (isset($event['data']['object']['type'])) { // Bancontact, Giropay, iDEAL
                $eventType .= "_" . $event['data']['object']['type'];
            } elseif (isset($event['data']['object']['source']['type'])) { // SOFORT and SEPA
                $eventType .= "_" . $event['data']['object']['source']['type'];
            } elseif (isset($event['data']['object']['source']['object'])) { // ACH bank accounts
                $eventType .= "_" . $event['data']['object']['source']['object'];
            } elseif (isset($event['data']['object']['payment_method_types'])) {
                $eventType .= "_" . implode("_", $event['data']['object']['payment_method_types']);
            } elseif (isset($event['data']['object']['payment_method_details'])) {
                $eventType .= "_" . $event['data']['object']['payment_method_details']['type'];
            }

            // Magento 2 event names do not allow numbers
            $eventType = str_replace("p24", "przelewy", $eventType);

            $this->log("Received $eventType");

            $this->eventManager->dispatch($eventType, array(
                'arrEvent' => $event,
                'stdEvent' => $stdEvent,
                'object'   => $event['data']['object']
            ));

            $this->cache($event);
            $this->log("200 OK");
        } catch (WebhookException $e) {
            $this->error($e->getMessage(), $e->statusCode, true);

            if (!empty($e->statusCode) && !empty($event) && ($e->statusCode < 400 || $e->statusCode > 499)) {
                $this->cache($event);
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
            $this->log($e->getTraceAsString());
            $this->error($e->getMessage());

            if (!empty($e->statusCode) && !empty($event) && ($e->statusCode < 400 || $e->statusCode > 499)) {
                $this->cache($event);
            }
        }
    }

    /**
     * Get Order Id From Object
     *
     * @param array|mixed|null $object
     * @return mixed
     * @throws WebhookException
     * @throws LocalizedException
     */
    public function getOrderIdFromObject($object)
    {
        // For most payment methods, the order ID is here
        if (!empty($object['metadata']['Order #'])) {
            return $object['metadata']['Order #'];
        }

        // For invoices created from the Magento admin, we have the order ID in the stripe_invoices DB table
        if ($object['object'] == 'invoice') {
            $entry = $this->invoiceFactory->create()->load($object['id'], 'invoice_id');
            if ($entry->getOrderIncrementId()) {
                return $entry->getOrderIncrementId();
            }

            foreach ($object['lines']['data'] as $lineItem) {
                // The invoice may include a subscription bought using stripe_payments_checkout_card
                if ($lineItem['type'] == "subscription" && !empty($lineItem['metadata']['Order #'])) {
                    return $lineItem['metadata']['Order #'];
                }
            }
        }

        // If the merchant refunds a charge of a recurring subscription order
        // from the Stripe dashboard, we need to drill down to the parent subscription
        if ($object['object'] == 'charge' && !empty($object['invoice']) && !empty($object['customer'])) {
            $this->config->reInitStripeFromCustomerId($object['customer']);
            $stripe = $this->config->getStripeClient();
            $invoice = $stripe->invoices->retrieve($object['invoice'], ['expand' => ['subscription']]);
            if (!empty($invoice->metadata->{"Order #"})) {
                return $invoice->metadata->{"Order #"};
            }
        }

        throw new WebhookException("Could not find the Order # associated with this webhook event");
    }

    /**
     * Init Stripe From
     *
     * @param $order
     * @param $event
     * @throws WebhookException
     */
    public function initStripeFrom($order, $event)
    {
        $paymentMethodCode = $order->getPayment()->getMethod();
        $orderId = $order->getId();

        if (strpos($paymentMethodCode, "stripe") !== 0) {
            throw new WebhookException("Order #$orderId was not placed using Stripe", 202);
        }

        // For multi-stripe account configurations, load the correct Stripe API key from the correct store view
        if (isset($event['data']['object']['livemode'])) {
            $mode = ($event['data']['object']['livemode'] ? "live" : "test");
        } else {
            $mode = null;
        }

        $this->config->reInitStripe($order->getStoreId(), $order->getOrderCurrencyCode(), $mode);
        $this->webhookCollection->pong($this->config->getPublishableKey($mode));
        $this->verifyWebhookSignature($order->getStoreId());
    }

    /**
     * Is Invoice Paid
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isInvoicePaid(OrderInterface $order): bool
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if (StripeInvoice::STRIPE_INVOICE_PAID == $invoice->getStripeInvoicePaid()) {
                return true;
            }
        }
        return false;
    }
}
