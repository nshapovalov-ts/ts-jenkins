<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Observer;

use Magento\Framework\Event\ObserverInterface;
use StripeIntegration\Payments\Exception\WebhookException;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order\Payment\Transaction\Builder;
use StripeIntegration\Payments\Model\ResourceModel\Source\CollectionFactory;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use StripeIntegration\Payments\Helper\RecurringOrder;
use StripeIntegration\Payments\Model\SubscriptionFactory;
use StripeIntegration\Payments\Model\Config;
use StripeIntegration\Payments\Helper\SepaCredit;
use StripeIntegration\Payments\Helper\Ach;
use StripeIntegration\Payments\Model\PaymentIntentFactory;
use StripeIntegration\Payments\Model\InvoiceFactory;
use StripeIntegration\Payments\Helper\Address;
use StripeIntegration\Payments\Helper\Subscriptions;
use StripeIntegration\Payments\Helper\Generic;
use StripeIntegration\Payments\Helper\Webhooks;
use Magento\Sales\Model\Order\Invoice;
use Stripe\Subscription;
use Exception;
use Magento\Sales\Model\Order;
use Magento\Framework\Event\Observer;
use Stripe\Exception\ApiErrorException;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice as StripeInvoice;
use Magento\Sales\Api\Data\InvoiceInterface;

/**
 * Class WebhooksObserver
 */
class WebhooksObserver implements ObserverInterface
{
    /**
     * @var Builder
     */
    private $transactionBuilder;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Webhooks
     */
    private $webhooksHelper;

    /**
     * @var Generic
     */
    private $paymentsHelper;

    /**
     * @var Subscriptions
     */
    private $subscriptionsHelper;

    /**
     * @var Address
     */
    private $addressHelper;

    /**
     * @var InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var PaymentIntentFactory
     */
    private $paymentIntentFactory;

    /**
     * @var Ach
     */
    private $achHelper;

    /**
     * @var SepaCredit
     */
    private $sepaCreditHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubscriptionFactory
     */
    private $subscriptionFactory;

    /**
     * @var RecurringOrder
     */
    private $recurringOrderHelper;

    /**
     * @var OrderCommentSender
     */
    private $orderCommentSender;

    /**
     * @var CollectionFactory
     */
    private $sourceCollectionFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var Transaction
     */
    private $dbTransaction;

    /**
     * WebhooksObserver constructor.
     *
     * @param Webhooks $webhooksHelper
     * @param Generic $paymentsHelper
     * @param Subscriptions $subscriptionsHelper
     * @param Address $addressHelper
     * @param InvoiceFactory $invoiceFactory
     * @param PaymentIntentFactory $paymentIntentFactory
     * @param Ach $achHelper
     * @param SepaCredit $sepaCreditHelper
     * @param Config $config
     * @param SubscriptionFactory $subscriptionFactory
     * @param RecurringOrder $recurringOrderHelper
     * @param OrderCommentSender $orderCommentSender
     * @param InvoiceService $invoiceService
     * @param Transaction $dbTransaction
     * @param CollectionFactory $sourceCollectionFactory
     * @param ManagerInterface $eventManager
     * @param CacheInterface $cache
     * @param Builder $transactionBuilder
     */
    public function __construct(
        Webhooks $webhooksHelper,
        Generic $paymentsHelper,
        Subscriptions $subscriptionsHelper,
        Address $addressHelper,
        InvoiceFactory $invoiceFactory,
        PaymentIntentFactory $paymentIntentFactory,
        Ach $achHelper,
        SepaCredit $sepaCreditHelper,
        Config $config,
        SubscriptionFactory $subscriptionFactory,
        RecurringOrder $recurringOrderHelper,
        OrderCommentSender $orderCommentSender,
        InvoiceService $invoiceService,
        Transaction $dbTransaction,
        CollectionFactory $sourceCollectionFactory,
        ManagerInterface $eventManager,
        CacheInterface $cache,
        Builder $transactionBuilder
    ) {
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

    /**
     * Order Age Less Than
     *
     * @param int $minutes
     * @param Order $order
     * @return bool
     */
    protected function orderAgeLessThan(int $minutes, Order $order)
    {
        $created = strtotime($order->getCreatedAt());
        $now = time();
        return (($now - $created) < ($minutes * 60));
    }

    /**
     * Was Captured From Admin
     *
     * @param mixed|array $object
     * @return bool
     */
    public function wasCapturedFromAdmin($object): bool
    {
        if (!empty($object['id'])
            && $this->cache->load("admin_captured_" . $object['id'])) {
            return true;
        }

        if (!empty($object['payment_intent']) && is_string($object['payment_intent'])
            && $this->cache->load("admin_captured_" . $object['payment_intent'])) {
            return true;
        }

        return false;
    }

    /**
     * Was Refunded From Admin
     *
     * @param mixed|array $object
     * @return bool
     */
    public function wasRefundedFromAdmin($object): bool
    {
        if (!empty($object['id']) && $this->cache->load("admin_refunded_" . $object['id'])) {
            return true;
        }

        return false;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();
        $arrEvent = $observer->getData('arrEvent');
        $stdEvent = $observer->getData('stdEvent');
        $object = $observer->getData('object');

        switch ($eventName) {
            // Creates an invoice for an order when the payment is captured from the Stripe dashboard
            case 'stripe_payments_webhook_charge_captured':
                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                if (empty($object['payment_intent'])) {
                    return;
                }

                $paymentIntentId = $object['payment_intent'];

                $captureCase = Invoice::CAPTURE_OFFLINE;
                $params = [
                    "amount"   => ($object['amount'] - $object['amount_refunded']),
                    "currency" => $object['currency']
                ];

                if ($this->wasCapturedFromAdmin($object)) {
                    return;
                }

                $this->paymentsHelper->invoiceOrder($order, $paymentIntentId, $captureCase, $params);
                break;

            case 'stripe_payments_webhook_review_closed':
                if (empty($object['payment_intent'])) {
                    return;
                }

                $paymentIntent = $this->paymentIntentFactory->create()->load($object['payment_intent'], 'pi_id');
                if (!$paymentIntent->getOrderIncrementId()) {
                    return;
                }

                $order = $this->webhooksHelper->loadOrderFromEvent($paymentIntent->getOrderIncrementId(), $arrEvent);

                $this->eventManager->dispatch(
                    'stripe_payments_review_closed_before',
                    ['order' => $order, 'object' => $object]
                );

                if ($object['reason'] == "approved") {
                    if (!$order->canHold()) {
                        $order->unhold();
                    }

                    $comment = __("The payment has been approved through Stripe.");
                    $order->addStatusToHistory(false, $comment, false);
                    $order->save();
                } else {
                    $comment = __(
                        "The payment was canceled through Stripe with reason: %1.",
                        ucfirst(str_replace("_", " ", $object['reason']))
                    );
                    $order->addStatusToHistory(false, $comment, false);
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
                $orderStatus = $order->getStatus();
                $order->addStatusToHistory($orderStatus, $comment, false);
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
                if ($this->wasRefundedFromAdmin($object)) {
                    return;
                }

                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->webhooksHelper->refund($order, $object);
                break;

            case 'stripe_payments_webhook_payment_intent_succeeded_fpx':
            case 'stripe_payments_webhook_payment_intent_succeeded_oxxo':
                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $paymentIntentId = $object['id'];
                $captureCase = Invoice::CAPTURE_OFFLINE;
                $params = [
                    "amount"   => $object['amount_received'],
                    "currency" => $object['currency']
                ];

                $invoice = $this->paymentsHelper->invoiceOrder($order, $paymentIntentId, $captureCase, $params);

                $payment = $order->getPayment();
                $transactionType = Order\Payment\Transaction::TYPE_CAPTURE;
                $payment->setLastTransId($paymentIntentId);
                $payment->setTransactionId($paymentIntentId);
                $transaction = $payment->addTransaction($transactionType, $invoice, true);
                $transaction->save();

                $comment = __("Payment succeeded.");
                if ($order->canUnhold()) {
                    $order->addStatusToHistory(false, $comment, false)
                        ->setHoldBeforeState('processing')
                        ->save();
                } else {
                    $order->addStatusToHistory($status = 'processing', $comment, false)
                        ->save();
                }
                break;

            case 'stripe_payments_webhook_payment_intent_payment_failed_card':
                // If this is empty, it was probably created by a payment method that we shouldn't handle here
                if (empty($object['metadata']['Order #'])) {
                    return;
                }

                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                if ($order->getPayment()->getMethod() != "stripe_payments_checkout_card") {
                    return;
                }

                if (!empty($object['last_payment_error']['message'])) {
                    switch ($object['last_payment_error']['code']) {
                        case 'payment_intent_authentication_failure':
                            $this->addOrderComment(
                                $order,
                                __("Payment failed: 3D Secure customer authentication failed.")->render()
                            );
                            break;
                        default:
                            $this->addOrderComment(
                                $order,
                                __("Payment failed: %1", $object['last_payment_error']['message'])->render()
                            );
                            break;
                    }
                }

                break;

            case 'stripe_payments_webhook_payment_intent_payment_failed_fpx':
                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail(
                    $order,
                    "Your order has been canceled because the payment authorization failed."
                );
                break;

            case 'stripe_payments_webhook_payment_intent_payment_failed_oxxo':
                $orderId = $object['metadata']['Order #'];
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail(
                    $order,
                    "Your order has been canceled because the voucher has not been paid before its expiry date."
                );
                break;

            case 'stripe_payments_webhook_source_transaction_created_sepa_credit_transfer':
                $collection = $this->sourceCollectionFactory->create();
                $sources = $collection->getSourcesById($object["source"]);
                if ($sources->count() == 0) {
                    throw new WebhookException(
                        __("Received %1 webhook but could find the source ID in the database.", $eventName)
                    );
                } else {
                    $source = $sources->getFirstItem();
                }

                $orderId = $source->getOrderIncrementId();
                if (empty($orderId)) {
                    throw new WebhookException(
                        __("Received %1 webhook but could find the order ID for the event.", $eventName)
                    );
                }

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->sepaCreditHelper->onTransactionCreated(
                    $order,
                    $source->getSourceId(),
                    $source->getStripeCustomerId(),
                    $object
                );

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
                if ($this->getPaymentMethod($object) == "klarna") {
                    $orderId = $this->webhooksHelper->getKlarnaOrderNumber($arrEvent);
                } else {
                    $orderId = $object['metadata']['Order #'];
                }

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
                if ($this->getPaymentMethod($object) == "klarna") {
                    $orderId = $this->webhooksHelper->getKlarnaOrderNumber($arrEvent);
                } else {
                    $orderId = $object['metadata']['Order #'];
                }

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $cancelled = $this->paymentsHelper->cancelOrCloseOrder($order);
                if ($cancelled) {
                    $this->addOrderCommentWithEmail(
                        $order,
                        "Sorry, your order has been canceled because a payment request was sent to your bank, but we did not receive a response back. Please contact us or place your order again."
                    );
                }
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
                if ($this->getPaymentMethod($object) == "klarna") {
                    $orderId = $this->webhooksHelper->getKlarnaOrderNumber($arrEvent);
                } else {
                    $orderId = $object['metadata']['Order #'];
                }

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);
                $this->addOrderCommentWithEmail(
                    $order,
                    "Your order has been canceled because the payment authorization failed."
                );
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
                if (in_array($this->getPaymentMethod($object), ["klarna", "ach_debit"])) {
                    $orderId = $object['metadata']['Order #'];
                } else {
                    $orderId = $object["source"]['metadata']['Order #'];
                }

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $payment = $order->getPayment();
                $payment->setTransactionId($object['id'])
                    ->setLastTransId($object['id'])
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(0)
                    ->setIsFraudDetected(false)
                    ->save();

                if (!isset($object["captured"])) {
                    break;
                }

                $invoiceCollection = $order->getInvoiceCollection();

                $lastInvoice = null;
                foreach ($invoiceCollection as $invoice) {
                    $lastInvoice = $invoice;
                }

                if ($object["captured"] == false) {
                    $transactionType = Order\Payment\Transaction::TYPE_AUTH;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    if ($lastInvoice) {
                        $lastInvoice->setTransactionId($object['id'])->save();
                    }
                } else {
                    $transactionType = Order\Payment\Transaction::TYPE_CAPTURE;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    if ($lastInvoice) {
                        $lastInvoice->setTransactionId($object['id'])
                            ->pay()->save();
                    }
                }

                $order->setState(Order::STATE_PROCESSING)
                    ->setStatus(Order::STATE_PROCESSING)
                    ->save();

                break;

            // Stripe Checkout
            case 'stripe_payments_webhook_charge_succeeded_card':
                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                if (empty($object['payment_method'])) {
                    return;
                }

                $paymentMethod = $this->config->getStripeClient()
                    ->paymentMethods->retrieve($object['payment_method'], []);

                switch ($order->getPayment()->getMethod()) {
                    case 'stripe_payments_checkout_card':
                        if (!empty($paymentMethod->customer)) {
                            $this->deduplicatePaymentMethod($object);
                        }

                        $order->setCanSendNewEmailFlag(true);
                        $this->paymentsHelper->sendNewOrderEmailFor($order);
                        break;

                    case 'stripe_payments':
                        if (!empty($paymentMethod->customer)) {
                            $this->deduplicatePaymentMethod($object);
                        }

                        return;

                    default:
                        return;
                }

                if (empty($object['payment_intent'])) {
                    throw new WebhookException("This charge was not created by a payment intent.");
                }

                $transactionId = $object['payment_intent'];

                $payment = $order->getPayment();
                $payment->setTransactionId($transactionId)
                    ->setLastTransId($transactionId)
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(0)
                    ->setIsFraudDetected(false)
                    ->save();

                if ($object["captured"] == false) {
                    $transactionType = Order\Payment\Transaction::TYPE_AUTH;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    if ($this->config->isAutomaticInvoicingEnabled()) {
                        $this->paymentsHelper->invoicePendingOrder($order, $transactionId);
                    }
                } else {
                    $transactionType = Order\Payment\Transaction::TYPE_CAPTURE;
                    $transaction = $payment->addTransaction($transactionType, null, false);
                    $transaction->save();

                    $this->paymentsHelper->invoiceOrder($order, $transactionId);
                }

                if ($order->getState() == Order::STATE_HOLDED) {
                    $order->addStatusToHistory(false, __("Payment succeeded."), false)->save();
                } else {
                    $order->setState(Order::STATE_PROCESSING)
                        ->addStatusToHistory(Order::STATE_PROCESSING, __("Payment succeeded."), false)
                        ->save();

                    if ($this->config->isStripeRadarEnabled()
                        && !empty($object['outcome']['type'])
                        && $object['outcome']['type'] == "manual_review") {
                        $this->paymentsHelper->holdOrder($order)->save();
                    }
                }

                // Update the billing address on the payment method if that is already attached to a customer
                if (!empty($paymentMethod->customer)) {
                    $this->config->getStripeClient()->paymentMethods->update(
                        $object['payment_method'],
                        [
                            'billing_details' => $this->addressHelper->getStripeAddressFromMagentoAddress(
                                $order->getBillingAddress()
                            )
                        ]
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
                if (in_array($this->getPaymentMethod($object), ["klarna", "ach_debit"])) {
                    $orderId = $object['metadata']['Order #'];
                } else {
                    $orderId = $object["source"]['metadata']['Order #'];
                }

                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);

                $this->paymentsHelper->cancelOrCloseOrder($order);

                if (!empty($object['failure_message'])) {
                    $msg = (string) __(
                        "Your order has been canceled. The payment authorization succeeded, however the authorizing provider declined the payment with the message: %1",
                        $object['failure_message']
                    );
                    $this->addOrderCommentWithEmail($order, $msg);
                } else {
                    $this->addOrderCommentWithEmail(
                        $order,
                        "Your order has been canceled. The payment authorization succeeded, however the authorizing provider declined the payment when a charge was attempted."
                    );
                }
                break;

            // Recurring subscription payments
            case 'stripe_payments_webhook_invoice_payment_succeeded':
                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);
                $paymentMethod = $order->getPayment()->getMethod();

                switch ($paymentMethod) {
                    case 'stripe_payments':
                        $subscriptionId = $this->getSubscriptionID($stdEvent);
                        $subscriptionModel = $this->subscriptionFactory->create()
                            ->load($subscriptionId, "subscription_id");
                        if (empty($subscriptionModel) || !$subscriptionModel->getId()) {
                            $subscription = Config::$stripeClient->subscriptions->retrieve($subscriptionId, []);
                            if (empty($subscription->metadata->{"Product ID"})) {
                                throw new WebhookException(
                                    __(
                                        "Subscription %1 was paid but there was no Product ID in the subscription's metadata.",
                                        $subscriptionId
                                    )
                                );
                            }

                            $productId = $subscription->metadata->{"Product ID"};
                            $product = $this->paymentsHelper->loadProductById($productId);
                            if (empty($product) || !$product->getId()) {
                                throw new WebhookException(
                                    __(
                                        "Subscription %1 was paid but the associated product with ID %1 could not be loaded.",
                                        $productId
                                    )
                                );
                            }

                            $subscriptionModel->initFrom($subscription, $order, $product)
                                ->setIsNew(false)
                                ->save();
                        }

                        // If this is a subscription order which was just placed,
                        // create an invoice for the order and return
                        if ($subscriptionModel->getIsNew()) {
                            $invoiceId = $stdEvent->data->object->id;
                            $invoice = $this->config->getStripeClient()->invoices->retrieve($invoiceId, [
                                'expand' => [
                                    'lines.data.price.product',
                                    'subscription',
                                    'payment_intent'
                                ]
                            ]);
                            if (empty($invoice->payment_intent)) {
                                // No payment was collected for this invoice (i.e. trial subscription only)
                                $paymentIntentModel = $this->paymentIntentFactory->create();
                                $paymentIntentModel->processTrialSubscriptionOrder($order, $invoice);
                                $order->save();
                                $order->setCanSendNewEmailFlag(true);
                                $this->paymentsHelper->notifyCustomer(
                                    $order,
                                    __(
                                        "Order #%1 has been received, but no payment was collected. You will receive a separate order email upon payment collection.",
                                        $order->getIncrementId()
                                    )
                                );
                            } else {
                                $this->paymentSucceeded($stdEvent, $order);
                            }

                            $subscriptionModel->setIsNew(false)->save();
                        } else {
                            // Otherwise, this is a recurring payment,
                            // so create a brand new order based on the original one
                            $invoiceId = $stdEvent->data->object->id;
                            $this->recurringOrderHelper->createFromInvoiceId($invoiceId);
                        }

                        break;

                    case 'stripe_payments_checkout_card':
                        $invoiceId = $stdEvent->data->object->id;

                        // If this is a subscription order which was just placed,
                        // create an invoice for the order and return
                        // @todo: Do we get here if the payment is fraudulent, and does a duplicate order get created?
                        if ($order->canInvoice()
                            || ($order->getTotalDue() == $order->getGrandTotal() && $order->getTotalDue() > 0)) {
                            if (empty($order->getPayment())) {
                                throw new WebhookException(
                                    "Order #%1 does not have any associated payment details.",
                                    $order->getIncrementId()
                                );
                            }

                            $checkoutSessionId = $order->getPayment()->getAdditionalInformation('checkout_session_id');
                            if (empty($checkoutSessionId)) {
                                throw new WebhookException(
                                    "Order #%1 is not associated with a valid Stripe Checkout Session.",
                                    $order->getIncrementId()
                                );
                            }

                            $paymentIntentModel = $this->paymentIntentFactory->create();

                            $invoice = $this->config->getStripeClient()->invoices->retrieve($invoiceId, [
                                'expand' => [
                                    'lines.data.price.product',
                                    'subscription',
                                    'payment_intent'
                                ]
                            ]);

                            if (empty($invoice->payment_intent)) {
                                // No payment was collected for this invoice (i.e. trial subscription only)
                                $paymentIntentModel->processTrialSubscriptionOrder($order, $invoice);
                                $order->save();
                                $order->setCanSendNewEmailFlag(true);
                                $this->paymentsHelper->notifyCustomer(
                                    $order,
                                    __(
                                        "Order #%1 has been received, but no payment was collected. You will receive a separate order email upon payment collection.",
                                        $order->getIncrementId()
                                    )
                                );
                                break;
                            }

                            $invoiceParams = [
                                "amount"   => $invoice->payment_intent->amount,
                                "currency" => $invoice->payment_intent->currency,
                                "shipping" => 0,
                                "tax"      => $invoice->tax,
                            ];

                            foreach ($invoice->lines->data as $invoiceLineItem) {
                                if (!empty($invoiceLineItem->price->product->metadata->{"Type"})
                                    && $invoiceLineItem->price->product->metadata->{"Type"} == "Shipping") {
                                    $invoiceParams["shipping"] +=
                                        $invoiceLineItem->price->unit_amount * $invoiceLineItem->quantity;
                                }
                            }

                            $this->paymentsHelper->setOrderTaxFrom(
                                $invoiceParams['tax'],
                                $invoiceParams['currency'],
                                $order
                            );

                            $paymentIntentModel->processAuthenticatedCheckoutOrder(
                                $order,
                                $invoice->payment_intent,
                                $invoiceParams
                            );

                            if ($invoice->payment_intent->status == "succeeded") {
                                $action = __("Captured");
                            } else {
                                if ($invoice->payment_intent->status == "requires_capture") {
                                    $action = __("Authorized");
                                } else {
                                    $action = __("Processed");
                                }
                            }

                            $amount = $this->paymentsHelper->getFormattedStripeAmount(
                                $invoice->payment_intent->amount,
                                $invoice->payment_intent->currency,
                                $order
                            );

                            $comment = __(
                                "%action amount %amount through Stripe.",
                                ['action' => $action, 'amount' => $amount]
                            );
                            $order->addStatusToHistory(Order::STATE_PROCESSING, $comment, false)->save();
                        } else {
                            // Otherwise, this is a recurring payment,
                            // so create a brand new order based on the original one
                            $this->recurringOrderHelper->createFromSubscriptionItems($invoiceId);
                        }

                        break;

                    case 'stripe_payments_invoice':
                        foreach ($order->getInvoiceCollection() as $invoice) {
                            $invoice->setStripeInvoicePaid(StripeInvoice::STRIPE_INVOICE_PAID);
                            $invoice->save();
                        }
                        $orderStatus = $order->getStatus();
                        $order->addStatusToHistory(
                            $orderStatus,
                            __("The invoice was successfully paid."),
                            false
                        )
                            ->save();
                        break;

                    default:
                        break;
                }

                break;

            case 'stripe_payments_webhook_invoice_payment_failed':
                $orderId = $this->webhooksHelper->getOrderIdFromObject($object);
                $order = $this->webhooksHelper->loadOrderFromEvent($orderId, $arrEvent);
                $paymentMethod = $order->getPayment()->getMethod();

                switch ($paymentMethod) {
                    case 'stripe_payments_invoice':
                        foreach ($order->getInvoiceCollection() as $invoice) {
                            $invoice->setStripeInvoicePaid(StripeInvoice::STRIPE_INVOICE_NOT_PAID_ERROR);
                            $invoice->save();
                        }

                        $orderStatus = $order->getStatus();
                        $order->addStatusToHistory(
                            $orderStatus,
                            __("There was an unsuccessful attempt to pay the invoice."),
                            false
                        )
                            ->save();
                        break;

                    default:
                        break;
                }

                break;

            // customer.source.updated, occurs when an ACH account is verified
            case 'stripe_payments_webhook_customer_source_updated':
                $helper = $this->achHelper;

                $data = $arrEvent['data'];
                if (!$helper->isACHBankAccountVerification($data)) {
                    return;
                }

                if (empty($data['object']['id']) || empty($data['object']['customer'])) {
                    return;
                }

                $orders = $helper->findOrdersFor($data['object']['id'], $data['object']['customer']);
                foreach ($orders as $order) {
                    $comment = __("Your bank account has been successfully verified.");
                    $this->addOrderCommentWithEmail($order, $comment->render());
                    try {
                        $this->webhooksHelper->initStripeFrom($order, $arrEvent);

                        $order->setState(Order::STATE_PENDING_PAYMENT)
                            ->setStatus(Order::STATE_PENDING_PAYMENT)
                            ->addStatusToHistory(
                                false,
                                __(
                                    "Attempting ACH charge for %1.",
                                    $order->formatPrice($order->getGrandTotal())
                                ),
                                false
                            )
                            ->save();

                        $order->getPayment();
                        $helper->charge($order);
                    } catch (Exception $e) {
                        $order->addStatusToHistory(false, $e->getMessage(), false);
                        $order->save();
                    }
                }

                break;

            default:
                # code...
                break;
        }
    }

    /**
     * Get Payment Method
     *
     * @param mixed|array $object
     * @return mixed|null
     */
    public function getPaymentMethod($object)
    {
        // Most APMs
        if (!empty($object["type"])) {
            return $object["type"];
        }

        // ACH Debit
        if (!empty($object["payment_method_details"]["type"])) {
            return $object["payment_method_details"]["type"];
        }

        return null;
    }

    /**
     * Add Order Comment With Email
     *
     * @param Order $order
     * @param string $comment
     */
    public function addOrderCommentWithEmail(Order $order, string $comment)
    {
        if (is_string($comment)) {
            $comment = __($comment);
        }

        try {
            $this->orderCommentSender->send($order, true, $comment);
        } catch (Exception $e) {
            // Just ignore this case
        }

        try {
            $order->addStatusToHistory(false, $comment, true);
            $order->save();
        } catch (Exception $e) {
            $this->webhooksHelper->log($e->getMessage(), $e);
        }
    }

    /**
     * Add Order Comment
     *
     * @param Order $order
     * @param string $comment
     * @throws Exception
     */
    public function addOrderComment(Order $order, string $comment)
    {
        $order->addStatusToHistory(false, $comment, false);
        $order->save();
    }

    /**
     * Get Subscription ID
     *
     * @param mixed|array $event
     * @return mixed|null
     * @throws Exception
     */
    private function getSubscriptionID($event)
    {
        if (empty($event->type)) {
            throw new Exception("Invalid event data");
        }

        switch ($event->type) {
            case 'invoice.payment_succeeded':
            case 'invoice.payment_failed':
                if (!empty($event->data->object->subscription)) {
                    return $event->data->object->subscription;
                }

                foreach ($event->data->object->lines->data as $data) {
                    if ($data->type == "subscription") {
                        return $data->id;
                    }
                }

                return null;

            case 'customer.subscription.deleted':
                if (!empty($event->data->object->id)) {
                    return $event->data->object->id;
                }
                break;

            default:
                return null;
        }

        return null;
    }

    /**
     * Payment Succeeded
     *
     * @param mixed|array $event
     * @param $order
     * @return InvoiceInterface|Invoice
     * @throws ApiErrorException
     * @throws WebhookException
     */
    public function paymentSucceeded($event, $order)
    {
        $subscriptionId = $this->getSubscriptionID($event);
        $paymentIntentId = $event->data->object->payment_intent;

        if (!isset($subscriptionId)) {
            throw new WebhookException(
                __("Received {$event->type} webhook but could not read the subscription object.")
            );
        }

        $subscription = Subscription::retrieve($subscriptionId);

        $metadata = $subscription->metadata;

        if (!empty($metadata->{'Order #'})) {
            $orderId = $metadata->{'Order #'};
        } else {
            throw new WebhookException(__("The webhook request has no Order ID in its metadata - ignoring."));
        }

        if (!empty($metadata->{'Product ID'})) {
            $productId = $metadata->{'Product ID'};
        } else {
            throw new WebhookException(__("The webhook request has no product ID in its metadata - ignoring."));
        }

        $currency = strtoupper($event->data->object->currency);

        if (isset($event->data->object->amount_paid)) {
            $amountPaid = $event->data->object->amount_paid;
        } else {
            if (isset($event->data->object->total)) {
                $amountPaid = $event->data->object->total;
            } else {
                $amountPaid = $subscription->amount;
            }
        }

        if ($amountPaid <= 0) {
            $order->addStatusToHistory(
                false,
                "This is a trialing subscription order, no payment has been collected yet. A new order will be created upon payment.",
                false
            );
            $order->save();
        }

        $productId = $metadata->{'Product ID'};
        $quantity = $subscription->quantity;
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                $item->setQtyInvoiced($item->getQtyOrdered() + $item->getQtyCanceled() - $quantity);
                $parent = $item->getParentItem();
                if ($parent) {
                    $parent->setQtyInvoiced($parent->getQtyOrdered() + $parent->getQtyCanceled() - $quantity);
                }
            } else {
                $item->setQtyInvoiced($item->getQtyOrdered() - $item->getQtyCanceled());
            }
        }

        return $this->paymentsHelper->invoiceSubscriptionOrder(
            $order,
            $paymentIntentId,
            Invoice::CAPTURE_OFFLINE,
            [
                "amount"   => $amountPaid,
                "currency" => $currency,
                "shipping" => $this->getShippingAmount($event),
                "tax"      => $this->getTaxAmount($event)
            ],
            true
        );
    }

    /**
     * Get Shipping Amount
     *
     * @param mixed|array $event
     * @return int
     */
    public function getShippingAmount($event): int
    {
        if (empty($event->data->object->lines->data)) {
            return 0;
        }

        foreach ($event->data->object->lines->data as $lineItem) {
            if (!empty($lineItem->description) && $lineItem->description == "Shipping") {
                return $lineItem->amount;
            }
        }

        return 0;
    }

    /**
     * Get Tax Amount
     *
     * @param $event
     * @return int
     */
    public function getTaxAmount($event): int
    {
        if (empty($event->data->object->tax)) {
            return 0;
        }

        return $event->data->object->tax;
    }

    /**
     * Deduplicate Payment Method
     *
     * @param array|mixed|null $object
     */
    public function deduplicatePaymentMethod($object)
    {
        if (!empty($object['customer']) && !empty($object['payment_method'])) {
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
