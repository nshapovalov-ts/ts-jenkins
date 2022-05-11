<?php

namespace StripeIntegration\Payments\Observer;

use Magento\Framework\Event\ObserverInterface;
use StripeIntegration\Payments\Helper\Logger;
use StripeIntegration\Payments\Exception\WebhookException;

class WebhooksObserver implements ObserverInterface
{
    public function __construct(
        \StripeIntegration\Payments\Helper\Webhooks $webhooksHelper,
        \StripeIntegration\Payments\Helper\Generic $paymentsHelper,
        \StripeIntegration\Payments\Helper\Subscriptions $subscriptionsHelper,
        \StripeIntegration\Payments\Helper\Address $addressHelper,
        \StripeIntegration\Payments\Model\InvoiceFactory $invoiceFactory,
        \StripeIntegration\Payments\Model\PaymentIntentFactory $paymentIntentFactory,
        \StripeIntegration\Payments\Helper\Ach $achHelper,
        \StripeIntegration\Payments\Helper\SepaCredit $sepaCreditHelper,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Model\SubscriptionFactory $subscriptionFactory,
        \StripeIntegration\Payments\Helper\RecurringOrder $recurringOrderHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $dbTransaction,
        \StripeIntegration\Payments\Model\ResourceModel\Source\CollectionFactory $sourceCollectionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder $transactionBuilder
    )
    {
        $this->webhooksHelper = $webhooksHelper;
        $this->paymentsHelper = $paymentsHelper;
        $this->subscriptionsHelper = $subscriptionsHelper;
        $this->addressHelper = $addressHelper;
        $this->invoiceFactory = $invoiceFactory;
        $this->paymentIntentFactory = $paymentIntentFactory;
        $this->achHelper = $achHelper;
        $this->sepaCreditHelper = $sepaCreditHelper;
        $this->config = $config;
        $this->subscriptionFactory = $subscriptionFactory;
        $this->recurringOrderHelper = $recurringOrderHelper;
        $this->orderCommentSender = $orderCommentSender;
        $this->sourceCollectionFactory = $sourceCollectionFactory;
        $this->eventManager = $eventManager;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->cache = $cache;
        $this->transactionBuilder = $transactionBuilder;
    }

    protected function orderAgeLessThan($minutes, $order)
    {
        $created = strtotime($order->getCreatedAt());
        $now = time();
        return (($now - $created) < ($minutes * 60));
    }

    public function wasCapturedFromAdmin($object)
    {
        if (!empty($object['id']) && $this->cache->load("admin_captured_" . $object['id']))
            return true;

        if (!empty($object['payment_intent']) && is_string($object['payment_intent']) && $this->cache->load("admin_captured_" . $object['payment_intent']))
            return true;

        return false;
    }

    public function wasRefundedFromAdmin($object)
    {
        if (!empty($object['id']) && $this->cache->load("admin_refunded_" . $object['id']))
            return true;

        return false;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $arrEvent = $observer->getData('arrEvent');
        $stdEvent = $observer->getData('stdEvent');
        $object = $observer->getData('object');

        switch ($eventName)
        {
            // Creates an invoice for an order when the payment is captured from the Stripe dashboard
            case 'stripe_payments_webhook_charge_captured':

                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                if (empty($object['payment_intent']))
                    return;

                $paymentIntentId = $object['payment_intent'];

                $captureCase = \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE;
                $params = [
                    "amount" => ($object['amount'] - $object['amount_refunded']),
                    "currency" => $object['currency']
                ];

                if ($this->wasCapturedFromAdmin($object))
                    return;

                $this->paymentsHelper->invoiceOrder($order, $paymentIntentId, $captureCase, $params);

                // $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                //     ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                //     ->save();

                break;

            case 'stripe_payments_webhook_review_closed':

                if (empty($object['payment_intent']))
                    return;

                $paymentIntent = $this->paymentIntentFactory->create()->load($object['payment_intent'], 'pi_id');
                if (!$paymentIntent->getOrderIncrementId())
                    return;

                $order = $this->webhooksHelper->loadOrderFromEvent($paymentIntent->getOrderIncrementId(), $arrEvent);

                $this->eventManager->dispatch(
                    'stripe_payments_review_closed_before',
                    ['order' => $order, 'object' => $object]
                );

                if ($object['reason'] == "approved")
                {
                    if (!$order->canHold())
                        $order->unhold();

                    $comment = __("The payment has been approved through Stripe.");
                    $order->addStatusToHistory(false, $comment, $isCustomerNotified = false);
                    $order->save();
                }
                else
                {
                    $comment = __("The payment was canceled through Stripe with reason: %1.", ucfirst(str_replace("_", " ", $object['reason'])));
                    $order->addStatusToHistory(false, $comment, $isCustomerNotified = false);
                    $order->save();
                }

                $this->eventManager->dispatch(
                    'stripe_payments_review_closed_after',
                    ['order' => $order, 'object' => $object]
                );

                break;

            case 'stripe_payments_webhook_invoice_finalized':

                $invoice = $this->invoiceFactory->create()->load($object['id'], 'invoice_id');
                $orderId = $invoice->getOrderIncrementId();
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);
                $comment = __("A payment is pending for this order. Invoice ID: %1", $invoice->getInvoiceId());
                $order->addStatusToHistory($status = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, $comment, $isCustomerNotified = false);
                $order->save();

                break;

            case 'stripe_payments_webhook_customer_subscription_created':

                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);
                $product = $this->webhooksHelper->loadSubscriptionProductFromEvent($arrEvent);
                $subscription = $stdEvent->data->object;
                $this->subscriptionsHelper->updateSubscriptionEntry($subscription, $order, $product);
                break;

            case 'stripe_payments_webhook_invoice_voided':

                $invoice = $this->invoiceFactory->create()->load($object['id'], 'invoice_id');
                $orderId = $invoice->getOrderIncrementId();
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);
                $this->webhooksHelper->refundOfflineOrCancel($order);
                break;

            case 'stripe_payments_webhook_charge_refunded':
            case 'stripe_payments_webhook_charge_refunded_card':
            case 'stripe_payments_webhook_charge_refunded_sepa_credit_transfer':
            case 'stripe_payments_webhook_charge_refunded_bank_account':

                if ($this->wasRefundedFromAdmin($object))
                    return;

                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->webhooksHelper->refund($order, $object);
                break;

            case 'stripe_payments_webhook_payment_intent_succeeded_fpx':
            case 'stripe_payments_webhook_payment_intent_succeeded_oxxo':

                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $paymentIntentId = $object['id'];
                $captureCase = \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE;
                $params = [
                    "amount" => $object['amount_received'],
                    "currency" => $object['currency']
                ];

                $invoice = $this->paymentsHelper->invoiceOrder($order, $paymentIntentId, $captureCase, $params);

                $payment = $order->getPayment();
                $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                $payment->setLastTransId($paymentIntentId);
                $payment->setTransactionId($paymentIntentId);
                $transaction = $payment->addTransaction($transactionType, $invoice, true);
                $transaction->save();

                $comment = __("Payment succeeded.");
                if ($order->canUnhold())
                {
                    $order->addStatusToHistory($status = false, $comment, $isCustomerNotified = false)
                        ->setHoldBeforeState('processing')
                        ->save();
                }
                else
                {
                    $order->addStatusToHistory($status = 'processing', $comment, $isCustomerNotified = false)
                        ->save();
                }

                break;

            case 'stripe_payments_webhook_payment_intent_payment_failed_card':

                // If this is empty, it was probably created by a payment method that we shouldn't handle here
                if (empty($object['metadata']['Order #']))
                    return;

                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                if ($order->getPayment()->getMethod() != "stripe_payments_checkout_card")
                    return;

                if (!empty($object['last_payment_error']['message']))
                {
                    switch ($object['last_payment_error']['code'])
                    {
                        case 'payment_intent_authentication_failure':
                            $this->addOrderComment($order, __("Payment failed: 3D Secure customer authentication failed."));
                            break;
                        default:
                            $this->addOrderComment($order, __("Payment failed: %1", $object['last_payment_error']['message']));
                            break;
                    }
                }

                break;

            case 'stripe_payments_webhook_payment_intent_payment_failed_fpx':

                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail($order, "Your order has been canceled because the payment authorization failed.");
                break;

            case 'stripe_payments_webhook_payment_intent_payment_failed_oxxo':

                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail($order, "Your order has been canceled because the voucher has not been paid before its expiry date.");
                break;

            case 'stripe_payments_webhook_source_transaction_created_sepa_credit_transfer':

                $collection = $this->sourceCollectionFactory->create();
                $sources = $collection->getSourcesById($object["source"]);
                if ($sources->count() == 0)
                    throw new WebhookException(__("Received %1 webhook but could find the source ID in the database.", $event->type));
                else
                    $source = $sources->getFirstItem();

                $orderId = $source->getOrderIncrementId();
                if (empty($orderId))
                    throw new WebhookException(__("Received %1 webhook but could find the order ID for the event.", $event->type));

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->sepaCreditHelper->onTransactionCreated($order, $source->getSourceId(), $source->getStripeCustomerId(), $object);

                break;

            case 'stripe_payments_webhook_source_chargeable_bancontact':
            case 'stripe_payments_webhook_source_chargeable_giropay':
            case 'stripe_payments_webhook_source_chargeable_ideal':
            case 'stripe_payments_webhook_source_chargeable_sepa_debit':
            case 'stripe_payments_webhook_source_chargeable_sofort':
            case 'stripe_payments_webhook_source_chargeable_multibanco':
            case 'stripe_payments_webhook_source_chargeable_eps':
            case 'stripe_payments_webhook_source_chargeable_przelewy':
            case 'stripe_payments_webhook_source_chargeable_alipay':
            case 'stripe_payments_webhook_source_chargeable_wechat':
            case 'stripe_payments_webhook_source_chargeable_klarna':

                if ($this->getPaymentMethod($object) == "klarna")
                    $orderId = $this->webhooksHelper->getKlarnaOrderNumber($arrEvent);
                else
                    $orderId = $object['metadata']['Order #'];

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->webhooksHelper->charge($order, $object);
                break;

            case 'stripe_payments_webhook_source_canceled_bancontact':
            case 'stripe_payments_webhook_source_canceled_giropay':
            case 'stripe_payments_webhook_source_canceled_ideal':
            case 'stripe_payments_webhook_source_canceled_sepa_debit':
            case 'stripe_payments_webhook_source_canceled_sofort':
            case 'stripe_payments_webhook_source_canceled_multibanco':
            case 'stripe_payments_webhook_source_canceled_eps':
            case 'stripe_payments_webhook_source_canceled_przelewy':
            case 'stripe_payments_webhook_source_canceled_alipay':
            case 'stripe_payments_webhook_source_canceled_wechat':
            case 'stripe_payments_webhook_source_canceled_klarna':
            case 'stripe_payments_webhook_source_canceled_sepa_credit_transfer':

                if ($this->getPaymentMethod($object) == "klarna")
                    $orderId = $this->webhooksHelper->getKlarnaOrderNumber($arrEvent);
                else
                    $orderId = $object['metadata']['Order #'];

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $cancelled = $this->paymentsHelper->cancelOrCloseOrder($order);
                if ($cancelled)
                    $this->addOrderCommentWithEmail($order, "Sorry, your order has been canceled because a payment request was sent to your bank, but we did not receive a response back. Please contact us or place your order again.");
                break;

            case 'stripe_payments_webhook_source_failed_bancontact':
            case 'stripe_payments_webhook_source_failed_giropay':
            case 'stripe_payments_webhook_source_failed_ideal':
            case 'stripe_payments_webhook_source_failed_sepa_debit':
            case 'stripe_payments_webhook_source_failed_sofort':
            case 'stripe_payments_webhook_source_failed_multibanco':
            case 'stripe_payments_webhook_source_failed_eps':
            case 'stripe_payments_webhook_source_failed_przelewy':
            case 'stripe_payments_webhook_source_failed_alipay':
            case 'stripe_payments_webhook_source_failed_wechat':
            case 'stripe_payments_webhook_source_failed_klarna':
            case 'stripe_payments_webhook_source_failed_sepa_credit_transfer':

                if ($this->getPaymentMethod($object) == "klarna")
                    $orderId = $this->webhooksHelper->getKlarnaOrderNumber($arrEvent);
                else
                    $orderId = $object['metadata']['Order #'];

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail($order, "Your order has been canceled because the payment authorization failed.");
                break;

            case 'stripe_payments_webhook_charge_succeeded_bancontact':
            case 'stripe_payments_webhook_charge_succeeded_giropay':
            case 'stripe_payments_webhook_charge_succeeded_ideal':
            case 'stripe_payments_webhook_charge_succeeded_sepa_debit':
            case 'stripe_payments_webhook_charge_succeeded_sofort':
            case 'stripe_payments_webhook_charge_succeeded_multibanco':
            case 'stripe_payments_webhook_charge_succeeded_eps':
            case 'stripe_payments_webhook_charge_succeeded_przelewy':
            case 'stripe_payments_webhook_charge_succeeded_alipay':
            case 'stripe_payments_webhook_charge_succeeded_wechat':
            case 'stripe_payments_webhook_charge_succeeded_klarna':
            case 'stripe_payments_webhook_charge_succeeded_sepa_credit_transfer':
            case 'stripe_payments_webhook_charge_succeeded_bank_account':

                if (in_array($this->getPaymentMethod($object), ["klarna", "ach_debit"]))
                    $orderId = $object['metadata']['Order #'];
                else
                    $orderId = $object["source"]['metadata']['Order #'];

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $payment = $order->getPayment();
                $payment->setTransactionId($object['id'])
                    ->setLastTransId($object['id'])
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(0)
                    ->setIsFraudDetected(false)
                    ->save();

                if (!isset($object["captured"]))
                    break;

                $invoiceCollection = $order->getInvoiceCollection();

                $lastInvoice = null;
                foreach ($invoiceCollection as $invoice)
                    $lastInvoice = $invoice;

                if ($object["captured"] == false)
                {
                    $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    if ($lastInvoice)
                        $invoice->setTransactionId($object['id'])->save();
                }
                else
                {
                    $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    if ($lastInvoice)
                        $invoice->setTransactionId($object['id'])
                                ->pay()->save();
                }

                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->save();

                break;

            // Stripe Checkout
            case 'stripe_payments_webhook_charge_succeeded_card':

                $orderId = $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                if (empty($object['payment_method']))
                    return;

                $paymentMethod = $this->config->getStripeClient()->paymentMethods->retrieve($object['payment_method'], []);

                switch ($order->getPayment()->getMethod())
                {
                    case 'stripe_payments_checkout_card':

                        if (!empty($paymentMethod->customer))
                            $this->deduplicatePaymentMethod($object);

                        $order->setCanSendNewEmailFlag(true);
                        $this->paymentsHelper->sendNewOrderEmailFor($order);
                        break;

                    case 'stripe_payments':

                        if (!empty($paymentMethod->customer))
                            $this->deduplicatePaymentMethod($object);

                        return;

                    default:
                        return;
                }

                if (empty($object['payment_intent']))
                    throw new WebhookException("This charge was not created by a payment intent.");

                $transactionId = $object['payment_intent'];

                $payment = $order->getPayment();
                $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId)
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(0)
                    ->setIsFraudDetected(false)
                    ->save();

                if ($object["captured"] == false)
                {
                    $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    if ($this->config->isAutomaticInvoicingEnabled())
                        $this->paymentsHelper->invoicePendingOrder($order, $transactionId);
                }
                else
                {
                    $transactionType = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    $invoice = $this->paymentsHelper->invoiceOrder($order, $transactionId);
                }

                if ($order->getState() == \Magento\Sales\Model\Order::STATE_HOLDED)
                {
                    $order->addStatusToHistory(false, __("Payment succeeded."), $isCustomerNotified = false)->save();
                }
                else
                {
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                        ->addStatusToHistory($status = \Magento\Sales\Model\Order::STATE_PROCESSING, __("Payment succeeded."), $isCustomerNotified = false)
                        ->save();

                    if ($this->config->isStripeRadarEnabled() && !empty($object['outcome']['type']) && $object['outcome']['type'] == "manual_review")
                        $this->paymentsHelper->holdOrder($order)->save();
                }

                // Update the billing address on the payment method if that is already attached to a customer
                if (!empty($paymentMethod->customer))
                {
                    $this->config->getStripeClient()->paymentMethods->update(
                        $object['payment_method'],
                        ['billing_details' => $this->addressHelper->getStripeAddressFromMagentoAddress($order->getBillingAddress())]
                    );
                }

                break;

            case 'stripe_payments_webhook_charge_failed_bancontact':
            case 'stripe_payments_webhook_charge_failed_giropay':
            case 'stripe_payments_webhook_charge_failed_ideal':
            case 'stripe_payments_webhook_charge_failed_sepa_debit':
            case 'stripe_payments_webhook_charge_failed_sofort':
            case 'stripe_payments_webhook_charge_failed_multibanco':
            case 'stripe_payments_webhook_charge_failed_eps':
            case 'stripe_payments_webhook_charge_failed_przelewy':
            case 'stripe_payments_webhook_charge_failed_alipay':
            case 'stripe_payments_webhook_charge_failed_wechat':
            case 'stripe_payments_webhook_charge_failed_klarna':
            case 'stripe_payments_webhook_charge_failed_sepa_credit_transfer':
            case 'stripe_payments_webhook_charge_failed_bank_account':

                if (in_array($this->getPaymentMethod($object), ["klarna", "ach_debit"]))
                    $orderId = $object['metadata']['Order #'];
                else
                    $orderId = $object["source"]['metadata']['Order #'];

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);

                if (!empty($object['failure_message']))
                {
                    $msg = (string)__("Your order has been canceled. The payment authorization succeeded, however the authorizing provider declined the payment with the message: %1", $object['failure_message']);
                    $this->addOrderCommentWithEmail($order, $msg);
                }
                else
                {
                    $this->addOrderCommentWithEmail($order, "Your order has been canceled. The payment authorization succeeded, however the authorizing provider declined the payment when a charge was attempted.");
                }
                break;

            // Recurring subscription payments
            case 'stripe_payments_webhook_invoice_payment_succeeded':

                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);
                $paymentMethod = $order->getPayment()->getMethod();

                switch ($paymentMethod)
                {
                    case 'stripe_payments':

                        $subscriptionId = $this->getSubscriptionID($stdEvent);
                        $subscriptionModel = $this->subscriptionFactory->create()->load($subscriptionId, "subscription_id");
                        if (empty($subscriptionModel) || !$subscriptionModel->getId())
                        {
                            $subscription = \StripeIntegration\Payments\Model\Config::$stripeClient->subscriptions->retrieve($subscriptionId, []);
                            if (empty($subscription->metadata->{"Product ID"}))
                                throw new WebhookException(__("Subscription %1 was paid but there was no Product ID in the subscription's metadata.", $subscriptionId));

                            $productId = $subscription->metadata->{"Product ID"};
                            $product = $this->paymentsHelper->loadProductById($productId);
                            if (empty($product) || !$product->getId())
                                throw new WebhookException(__("Subscription %1 was paid but the associated product with ID %1 could not be loaded.", $productId));

                            $subscriptionModel->initFrom($subscription, $order, $product)
                                ->setIsNew(false)
                                ->save();
                        }

                        // If this is a subscription order which was just placed, create an invoice for the order and return
                        if ($subscriptionModel->getIsNew())
                        {
                            $invoiceId = $stdEvent->data->object->id;
                            $invoice = $this->config->getStripeClient()->invoices->retrieve($invoiceId, [
                                'expand' => [
                                    'lines.data.price.product',
                                    'subscription',
                                    'payment_intent'
                                ]
                            ]);
                            if (empty($invoice->payment_intent))
                            {
                                // No payment was collected for this invoice (i.e. trial subscription only)
                                $paymentIntentModel = $this->paymentIntentFactory->create();
                                $paymentIntentModel->processTrialSubscriptionOrder($order, $invoice);
                                $order->save();
                                $order->setCanSendNewEmailFlag(true);
                                $this->paymentsHelper->notifyCustomer($order, __("Order #%1 has been received, but no payment was collected. You will receive a separate order email upon payment collection.", $order->getIncrementId()));
                            }
                            else
                            {
                                $this->paymentSucceeded($stdEvent, $order);
                            }

                            $subscriptionModel->setIsNew(false)->save();
                        }
                        else
                        {
                            // Otherwise, this is a recurring payment, so create a brand new order based on the original one
                            $invoiceId = $stdEvent->data->object->id;
                            $this->recurringOrderHelper->createFromInvoiceId($invoiceId);
                        }

                        break;

                    case 'stripe_payments_checkout_card':

                        $invoiceId = $stdEvent->data->object->id;

                        // If this is a subscription order which was just placed, create an invoice for the order and return
                        // @todo: Do we get here if the payment is fraudulent, and does a duplicate order get created?
                        if ($order->canInvoice() || ($order->getTotalDue() == $order->getGrandTotal() && $order->getTotalDue() > 0))
                        {
                            if (empty($order->getPayment()))
                                throw new WebhookException("Order #%1 does not have any associated payment details.", $order->getIncrementId());

                            $checkoutSessionId = $order->getPayment()->getAdditionalInformation('checkout_session_id');
                            if (empty($checkoutSessionId))
                                throw new WebhookException("Order #%1 is not associated with a valid Stripe Checkout Session.", $order->getIncrementId());

                            $paymentIntentModel = $this->paymentIntentFactory->create();

                            $invoice = $this->config->getStripeClient()->invoices->retrieve($invoiceId, [
                                'expand' => [
                                    'lines.data.price.product',
                                    'subscription',
                                    'payment_intent'
                                ]
                            ]);

                            $currency = $order->getCurrencyCode();

                            if (empty($invoice->payment_intent))
                            {
                                // No payment was collected for this invoice (i.e. trial subscription only)
                                $paymentIntentModel->processTrialSubscriptionOrder($order, $invoice);
                                $order->save();
                                $order->setCanSendNewEmailFlag(true);
                                $this->paymentsHelper->notifyCustomer($order, __("Order #%1 has been received, but no payment was collected. You will receive a separate order email upon payment collection.", $order->getIncrementId()));
                                break;
                            }

                            $invoiceParams = [
                                "amount" => $invoice->payment_intent->amount,
                                "currency" => $invoice->payment_intent->currency,
                                "shipping" => 0,
                                "tax" => $invoice->tax,
                            ];

                            foreach ($invoice->lines->data as $invoiceLineItem)
                            {
                                if (!empty($invoiceLineItem->price->product->metadata->{"Type"}) && $invoiceLineItem->price->product->metadata->{"Type"} == "Shipping")
                                {
                                    $invoiceParams["shipping"] += $invoiceLineItem->price->unit_amount * $invoiceLineItem->quantity;
                                }
                            }

                            $this->paymentsHelper->setOrderTaxFrom($invoiceParams['tax'], $invoiceParams['currency'], $order);

                            $paymentIntentModel->processAuthenticatedCheckoutOrder($order, $invoice->payment_intent, $invoiceParams);

                            if ($invoice->payment_intent->status == "succeeded")
                                $action = __("Captured");
                            else if ($invoice->payment_intent->status == "requires_capture")
                                $action = __("Authorized");
                            else
                                $action = __("Processed");

                            $amount = $this->paymentsHelper->getFormattedStripeAmount($invoice->payment_intent->amount, $invoice->payment_intent->currency, $order);
                            $comment = __("%action amount %amount through Stripe.", ['action' => $action, 'amount' => $amount]);
                            $order->addStatusToHistory($status = \Magento\Sales\Model\Order::STATE_PROCESSING, $comment, $isCustomerNotified = false)->save();
                        }
                        else
                        {
                            // Otherwise, this is a recurring payment, so create a brand new order based on the original one
                            $this->recurringOrderHelper->createFromSubscriptionItems($invoiceId);
                        }

                        break;

                    case 'stripe_payments_invoice':

                        $order->getPayment()->setLastTransId($object['payment_intent'])->save();

                        foreach($order->getInvoiceCollection() as $invoice)
                        {
                            $invoice->setTransactionId($object['payment_intent']);
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                            $invoice->pay();
                            $invoice->save();
                        }

                        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                                ->addStatusToHistory($status = \Magento\Sales\Model\Order::STATE_PROCESSING, __("The customer has paid the invoice for this order."), $isCustomerNotified = false)
                                ->save();

                        break;

                    default:
                        # code...
                        break;
                }

                break;

            case 'stripe_payments_webhook_invoice_payment_failed':
                //$this->paymentFailed($event);
                break;

            // customer.source.updated, occurs when an ACH account is verified
            case 'stripe_payments_webhook_customer_source_updated':

                $helper = $this->achHelper;

                $data = $arrEvent['data'];
                if (!$helper->isACHBankAccountVerification($data))
                    return;

                if (empty($data['object']['id']) || empty($data['object']['customer']))
                    return;

                $orders = $helper->findOrdersFor($bankAccountId = $data['object']['id'], $customerId = $data['object']['customer']);
                foreach ($orders as $order)
                {
                    $comment = __("Your bank account has been successfully verified.");
                    $this->addOrderCommentWithEmail($order, $comment);
                    try
                    {
                        $this->webhooksHelper->initStripeFrom($order, $arrEvent);

                        $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                            ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                            ->addStatusToHistory($status = false, __("Attempting ACH charge for %1.", $order->formatPrice($order->getGrandTotal())), $isCustomerNotified = false)
                            ->save();

                        $payment = $order->getPayment();

                        $charge = $helper->charge($order);

                    }
                    catch (\Exception $e)
                    {
                        $order->addStatusToHistory($status = false, $e->getMessage(), $isCustomerNotified = false);
                        $order->save();
                    }
                }

                break;

            default:
                # code...
                break;
        }
    }

    public function getPaymentMethod($object)
    {
        // Most APMs
        if (!empty($object["type"]))
            return $object["type"];

        // ACH Debit
        if (!empty($object["payment_method_details"]["type"]))
            return $object["payment_method_details"]["type"];

        return null;
    }

    public function addOrderCommentWithEmail($order, $comment)
    {
        if (is_string($comment))
            $comment = __($comment);

        try
        {
            $this->orderCommentSender->send($order, $notify = true, $comment);
        }
        catch (\Exception $e)
        {
            // Just ignore this case
        }

        try
        {
            $order->addStatusToHistory($status = false, $comment, $isCustomerNotified = true);
            $order->save();
        }
        catch (\Exception $e)
        {
            $this->webhooksHelper->log($e->getMessage(), $e);
        }
    }

    public function addOrderComment($order, $comment)
    {
        $order->addStatusToHistory($status = false, $comment, $isCustomerNotified = false);
        $order->save();
    }


    private function getSubscriptionID($event)
    {
        if (empty($event->type))
            throw new \Exception("Invalid event data");

        switch ($event->type)
        {
            case 'invoice.payment_succeeded':
            case 'invoice.payment_failed':
                if (!empty($event->data->object->subscription))
                    return $event->data->object->subscription;

                foreach ($event->data->object->lines->data as $data)
                {
                    if ($data->type == "subscription")
                        return $data->id;
                }

                return null;

            case 'customer.subscription.deleted':
                if (!empty($event->data->object->id))
                    return $event->data->object->id;
                break;

            default:
                return null;
        }
    }

    public function paymentSucceeded($event, $order)
    {
        $subscriptionId = $this->getSubscriptionID($event);
        $paymentIntentId = $event->data->object->payment_intent;

        if (!isset($subscriptionId))
            throw new WebhookException(__("Received {$event->type} webhook but could not read the subscription object."));

        $subscription = \Stripe\Subscription::retrieve($subscriptionId);

        $metadata = $subscription->metadata;

        if (!empty($metadata->{'Order #'}))
            $orderId = $metadata->{'Order #'};
        else
            throw new WebhookException(__("The webhook request has no Order ID in its metadata - ignoring."));

        if (!empty($metadata->{'Product ID'}))
            $productId = $metadata->{'Product ID'};
        else
            throw new WebhookException(__("The webhook request has no product ID in its metadata - ignoring."));

        $currency = strtoupper($event->data->object->currency);

        if (isset($event->data->object->amount_paid))
            $amountPaid = $event->data->object->amount_paid;
        else if (isset($event->data->object->total))
            $amountPaid = $event->data->object->total;
        else
            $amountPaid = $subscription->amount;

        if ($amountPaid <= 0)
        {
            $order->addStatusToHistory(
                $status = false,
                "This is a trialing subscription order, no payment has been collected yet. A new order will be created upon payment.",
                $isCustomerNotified = false
            );
            $order->save();
            return;
        }

        $productId = $metadata->{'Product ID'};
        $quantity = $subscription->quantity;
        foreach ($order->getAllItems() as $item)
        {
            if ($item->getProductId() == $productId)
            {
                $item->setQtyInvoiced($item->getQtyOrdered() + $item->getQtyCanceled() - $quantity);
                $parent = $item->getParentItem();
                if ($parent)
                    $parent->setQtyInvoiced($parent->getQtyOrdered() + $parent->getQtyCanceled() - $quantity);
            }
            else
                $item->setQtyInvoiced($item->getQtyOrdered() - $item->getQtyCanceled());
        }

        return $this->paymentsHelper->invoiceSubscriptionOrder(
            $order,
            $paymentIntentId,
            \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE,
            ["amount" => $amountPaid, "currency" => $currency, "shipping" => $this->getShippingAmount($event), "tax" => $this->getTaxAmount($event)],
            true
        );
    }

    public function getShippingAmount($event)
    {
        if (empty($event->data->object->lines->data))
            return 0;

        foreach ($event->data->object->lines->data as $lineItem)
        {
            if (!empty($lineItem->description) && $lineItem->description == "Shipping")
            {
                return $lineItem->amount;
            }
        }
    }

    public function getTaxAmount($event)
    {
        if (empty($event->data->object->tax))
            return 0;

        return $event->data->object->tax;
    }

    public function deduplicatePaymentMethod($object)
    {
        if (!empty($object['customer']) && !empty($object['payment_method']))
        {
            $type = $object['payment_method_details']['type'];
            $this->paymentsHelper->deduplicatePaymentMethod(
                $object['customer'],
                $object['payment_method'],
                $type,
                $object['payment_method_details'][$type]['fingerprint'],
                $this->config->getStripeClient()
            );
        }
    }
}
