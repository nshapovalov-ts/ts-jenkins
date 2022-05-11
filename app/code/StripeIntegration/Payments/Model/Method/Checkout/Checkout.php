<?php

namespace StripeIntegration\Payments\Model\Method\Checkout;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use StripeIntegration\Payments\Helper;
use StripeIntegration\Payments\Helper\Logger;
use Magento\Framework\Exception\CouldNotSaveException;

abstract class Checkout extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $type = '';
    protected $_code = '';
    // protected $_formBlockType = 'StripeIntegration\Payments\Block\Method\Checkout';
    protected $_infoBlockType = 'StripeIntegration\Payments\Block\Info';

    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canCaptureOnce = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isGateway = true;
    protected $_isInitializeNeeded = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canUseForMultishipping  = false;
    protected $_canCancelInvoice = true;
    protected $_canUseCheckout = true;
    protected $_canSaveCc = false;

    protected $stripeCustomer = null;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \StripeIntegration\Payments\Model\Config $config,
        \StripeIntegration\Payments\Helper\Generic $helper,
        \StripeIntegration\Payments\Helper\Api $api,
        \StripeIntegration\Payments\Model\PaymentIntent $paymentIntent,
        \StripeIntegration\Payments\Model\Stripe\CouponFactory $couponFactory,
        \StripeIntegration\Payments\Helper\Subscriptions $subscriptions,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->cache = $context->getCacheManager();
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->taxHelper = $taxHelper;

        $this->config = $config;
        $this->helper = $helper;
        $this->api = $api;
        $this->paymentIntent = $paymentIntent;
        $this->customer = $helper->getCustomerModel();
        $this->logger = $logger;
        $this->request = $request;
        $this->checkoutHelper = $checkoutHelper;
        $this->scopeConfig = $scopeConfig;
        $this->couponFactory = $couponFactory;
        $this->subscriptions = $subscriptions;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->config->initStripe())
            return false;

        if (parent::isAvailable($quote) === false)
            return false;

        if (!$this->isActive($quote ? $quote->getStoreId() : null))
            return false;

        if (!$quote)
            return false;

        return true;
    }

    public function adjustParamsForMethod(&$params, $payment, $order, $quote)
    {
        // Overwrite this method to specify custom params for this method
    }

    public function reset()
    {
        $this->stripeCustomer = null;
        $session = $this->checkoutHelper->getCheckout();
        $session->setStripePaymentsCheckoutSessionId(null);
    }

    protected function cleanPaymentIntentData($data)
    {
        $supportedParams = ['application_fee_amount', 'capture_method', 'description', 'metadata', 'on_behalf_of', 'receipt_email', 'setup_future_usage', 'shipping', 'statement_descriptor', 'statement_descriptor_suffix', 'transfer_data', 'transfer_group'];

        $params = [];

        foreach ($data as $key => $value)
            if (in_array($key, $supportedParams))
                $params[$key] = $value;

        return $params;
    }

    public function getSubscriptionShipping($order, $orderItem, $subscriptionProfile)
    {
        if (!is_numeric($subscriptionProfile['shipping_stripe']) || $subscriptionProfile['shipping_stripe'] <= 0)
            return null;

        $lineItem = [
            'price_data' => [
                'currency' => $order->getOrderCurrencyCode(),
                'product_data' => [
                    'name' => __("Shipping for %1", $orderItem->getName()),
                    'metadata' => [
                        'Type' => 'Shipping'
                    ]
                ],
                'unit_amount' => $subscriptionProfile['shipping_stripe']
            ],
            'quantity' => 1
        ];

        $recurringData = $this->getRecurringData($subscriptionProfile['interval'], $subscriptionProfile['interval_count']);
        $lineItem = array_merge_recursive($lineItem, $recurringData);
        $shippingTaxData = $this->getShippingTaxData();
        $lineItem = array_merge_recursive($lineItem, $shippingTaxData);

        return $lineItem;
    }

    public function getLineItemsForOrder($order, $subscriptions)
    {
        $currency = $order->getOrderCurrencyCode();
        $cents = $this->helper->isZeroDecimal($currency) ? 1 : 100;
        $orderItems = $order->getAllVisibleItems();
        $lines = [];
        $lineItemsTax = 0;
        $subscriptionsShipping = 0;
        $this->hasInitialFees = false;
        $this->hasShipping = false;
        $this->hasSubscriptions = false;
        $this->hasRegularProducts = false;
        $this->hasTax = false;

        if (!empty($subscriptions))
            $addTaxRateToLineItems = true;
        else
            $addTaxRateToLineItems = false;

        foreach ($orderItems as $orderItem)
        {
            if ($orderItem->getParentItem()) // Skip configurable products
                continue;

            $product = $this->helper->loadProductById($orderItem->getProductId());

            if (!$product->getId()) // The product has been deleted
                continue;

            $profile = null;
            if (isset($subscriptions[$orderItem->getQuoteItemId()]['profile']))
            {
                $this->hasSubscriptions = true;
                $profile = $subscriptions[$orderItem->getQuoteItemId()]['profile'];
            }
            else
                $this->hasRegularProducts = true;

            if ($this->config->priceIncludesTax())
                $price = $orderItem->getPriceInclTax();
            else
                $price = $orderItem->getPrice();

            $lineItem = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => $orderItem->getName(),
                        'images' => [$this->helper->getProductImage($product)],
                        'metadata' => [
                            'Type' => 'Product',
                            'Product ID' => $orderItem->getProductId()
                        ]
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($price, $currency)
                ],
                'quantity' => $orderItem->getQtyOrdered()
            ];

            if ($profile) // Add the 'recurring' parameter
            {
                $recurringData = $this->getRecurringData($profile['interval'], $profile['interval_count']);
                $lineItem = array_merge_recursive($lineItem, $recurringData);
            }

            $taxRate = $this->subscriptions->retrieveTaxRate($orderItem->getTaxPercent(), $this->config->priceIncludesTax());
            if ($addTaxRateToLineItems)
            {
                $lineItem['tax_rates'] = [ $taxRate ];
                $lineItemsTax += $orderItem->getTaxAmount();
                $this->hasTax = true;
            }

            $lines[] = $lineItem;

            if ($profile && $this->subscriptions->chargeShippingRecurringly())
            {
                if (is_numeric($profile['shipping_stripe']) && $profile['shipping_stripe'] > 0)
                {
                    $lineItemsTax += $subscriptions[$orderItem->getQuoteItemId()]['profile']['tax_amount_shipping'];
                    $subscriptionsShipping += $subscriptions[$orderItem->getQuoteItemId()]['profile']['shipping_magento'];
                }
            }

            if (!empty($profile['initial_fee_stripe']) && $profile['initial_fee_stripe'] > 0)
            {
                $initialFee = $profile['initial_fee_stripe'];
                // $currency = $profile['currency'];
                $currency = $order->getOrderCurrencyCode();

                $lineItem = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => __("Initial fee for %1", $orderItem->getName()),
                            'metadata' => [
                                'Type' => 'Initial fee'
                            ]
                        ],
                        'unit_amount' => $initialFee
                    ],
                    'quantity' => $profile['qty']
                ];

                if ($addTaxRateToLineItems)
                {
                    $lineItem['tax_rates'] = [ $taxRate ];
                    $lineItemsTax += $profile['tax_amount_initial_fee'];
                    $this->hasTax = true;
                }

                $lines[] = $lineItem;
                $this->hasInitialFees = true;
            }
        }

        if ($subscriptionsShipping > 0)
        {
            if (!$this->hasRegularProducts) // Solves a rounding issue
            {
                if ($this->config->shippingIncludesTax())
                {
                    $lineItemsTax += $order->getShippingTaxAmount();
                    $subscriptionsShipping = $order->getShippingInclTax();
                }
                else
                    $subscriptionsShipping = $order->getShippingAmount();
            }

            $lineItem = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => __("Shipping for subscription items"),
                        'metadata' => [
                            'Type' => 'Shipping'
                        ]
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($subscriptionsShipping, $currency)
                ],
                'quantity' => 1
            ];

            $shippingTaxData = $this->getShippingTaxData();
            $lineItem = array_merge_recursive($lineItem, $shippingTaxData);
            $lineItem = array_merge_recursive($lineItem, $recurringData);
            $lines[] = $lineItem;
            $this->hasShipping = true;
        }

        if ($this->config->shippingIncludesTax())
            $orderShipping = $order->getShippingInclTax();
        else
            $orderShipping = $order->getShippingAmount();

        $remainingShipping = round($orderShipping - $subscriptionsShipping, 2);
        if ($remainingShipping > 0)
        {
            $lineItem = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => __("Shipping"),
                        'metadata' => [
                            'Type' => 'Shipping'
                        ]
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($remainingShipping, $currency)
                ],
                'quantity' => 1
            ];

            if ($addTaxRateToLineItems)
            {
                $shippingTaxData = $this->getShippingTaxData();
                $lineItem = array_merge_recursive($lineItem, $shippingTaxData);
                $lineItemsTax += $order->getShippingTaxAmount();
                $this->hasTax = true;
            }

            $lines[] = $lineItem;
            $this->hasShipping = true;
        }

        $remainingTax = round($order->getTaxAmount() - $lineItemsTax, 2);
        if ($remainingTax > 0)
        {
            $lines[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => __("Tax"),
                        'metadata' => [
                            'Type' => 'Tax'
                        ]
                    ],
                    'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount($remainingTax, $currency)
                ],
                'quantity' => 1
            ];
            $this->hasTax = true;
        }

        return $lines;
    }

    public function getRecurringData($interval, $intervalCount)
    {
        return [
            'price_data' => [
                'recurring' => [
                    'interval' => $interval,
                    'interval_count' => $intervalCount
                ]
            ]
        ];
    }

    public function getShippingTaxData()
    {
        $data = [];

        $shippingTaxPercent = $this->subscriptions->getShippingTax("percent");
        if (is_numeric($shippingTaxPercent) && $shippingTaxPercent > 0)
        {
            $taxRate = $this->subscriptions->retrieveTaxRate($shippingTaxPercent, $this->config->shippingIncludesTax());
            $data['tax_rates'] = [ $taxRate ];
        }

        return $data;
    }

    public function checkIfDiscountIsSupported($stripeCoupon)
    {
        $hasSubscriptions = $this->hasSubscriptions;
        $hasRegularProducts = $this->hasRegularProducts;
        $hasInitialFees = $this->hasInitialFees;
        $hasShipping = $this->hasShipping;
        $hasTax = $this->hasTax;
        $discountAppliesOnShipping = $stripeCoupon->rule->getApplyToShipping();
        $isPercentDiscount = !empty($stripeCoupon->getStripeObject()->percent_off);
        $isAmountDiscount = !empty($stripeCoupon->getStripeObject()->amount_off);
        $hasDiscount = ($isPercentDiscount || $isAmountDiscount);
        $isDiscountSupported = true;
        $applyTaxAfterDiscount = $this->taxHelper->applyTaxAfterDiscount();

        // Initial fees are currently not discounted in the implementation, we may add this as a feature in a future version
        if ($hasSubscriptions && $hasInitialFees && $isPercentDiscount)
            throw new LocalizedException(__("This discount coupon cannot be applied on this order. Please remove the coupon and try again (err: 1)"));

        // Not supported by Stripe; December 2020
        if ($hasSubscriptions && $hasShipping && $isPercentDiscount && !$discountAppliesOnShipping)
            throw new LocalizedException(__("This discount coupon cannot be applied on this order. Please remove the coupon and try again (err: 2)"));

        // We do not know how much of the amount was meant for subscriptions and how much was meant for regular products
        if ($hasSubscriptions && $hasRegularProducts && $isAmountDiscount)
            throw new LocalizedException(__("This discount coupon cannot be applied on this order. Please remove the coupon and try again (err: 3)"));

        // Not supported by Stripe; December 2020
        if ($hasDiscount && $hasTax && !$applyTaxAfterDiscount)
            throw new LocalizedException(__("This discount coupon cannot be applied on this order. Please remove the coupon and try again (err: 4)"));
    }

    public function areInvoicedTogether($subscriptions)
    {
        $startingTimes = [];
        $endingTimes = [];
        $now = time();

        foreach ($subscriptions as $subscription)
        {
            $starts = $now;
            if (!empty($subscription['profile']['trial_end']))
                $starts = $subscription['profile']['trial_end'];
            else if (!empty($subscription['profile']['trial_days']))
                $starts = strtotime("+" . $subscription['profile']['trial_days'] . " days");

            $ends = $starts + strtotime("+" . $subscription['profile']['interval_count'] . " " . $subscription['profile']['interval']);

            $startingTimes[$starts] = $starts;
            $endingTimes[$ends] = $ends;
        }

        if (count($startingTimes) > 1)
            return false;

        if (count($endingTimes) > 1)
            return false;

        return true;
    }

    public function checkIfCartIsSupported($subscriptions)
    {
        if (!$this->areInvoicedTogether($subscriptions))
            throw new LocalizedException(__("Sorry, it is not possible to buy subscriptions that are billed on different dates. All subscription items must be billed on the same date. Please buy the subscriptions separately."));
    }

    protected function hasTrialSubscriptions($subscriptions)
    {
        foreach ($subscriptions as $subscription)
        {
            if (!empty($subscription['profile']['trial_end']))
                return true;

            if (!empty($subscription['profile']['trial_days']))
                return true;
        }

        return false;
    }

    protected function getSessionParams($order)
    {
        $returnUrl = $this->urlBuilder->getUrl('stripe/payment/index', [
            '_secure' => $this->request->isSecure(),
            'payment_method' => $this->type
        ]);

        $amount = $order->getGrandTotal();
        $currency = $order->getOrderCurrencyCode();
        $subscriptions = $this->subscriptions->getSubscriptionsFromOrder($order);
        $lineItems = $this->getLineItemsForOrder($order, $subscriptions);

        $params = [
            'cancel_url' => $returnUrl,
            'payment_method_types' => ['card'],
            'success_url' => $returnUrl,
            'client_reference_id' => $order->getIncrementId(),
            'metadata' => [
                'Order #' => $order->getIncrementId(),
                'Payment Method' => 'Stripe Checkout'
            ],
            'locale' => $this->config->getCheckoutLocale(),
            'line_items' => $lineItems
        ];

        if (!empty($subscriptions))
        {
            $params["mode"] = "subscription";

            $this->checkIfCartIsSupported($subscriptions);

            foreach ($subscriptions as $orderItemId => $subscription)
            {
                $product = $subscription['product'];
                $orderItem = $subscription['order_item'];
                $profile = $subscription['profile'];

                $params["subscription_data"] = [
                    "metadata" => $this->subscriptions->collectMetadata($profile, $product, $order, $orderItem)
                ];

                if ($profile['trial_days'] > 0)
                    $params["subscription_data"]['trial_period_days'] = $profile['trial_days'];
            }
        }
        else
        {
            $params["mode"] = "payment";
            $params["payment_intent_data"] = $this->cleanPaymentIntentData($this->paymentIntent->getParamsFrom($order->getQuote(), $order));
            $params["submit_type"] = "pay";
        }

        if ($order->getDiscountAmount() < 0)
        {
            $stripeCoupon = $this->couponFactory->create()->fromOrder($order);
            if ($stripeCoupon->getId())
            {
                $this->checkIfDiscountIsSupported($stripeCoupon);
                $params['discounts'] = [['coupon' => $stripeCoupon->getId()]];
            }
        }

        if ($this->config->getSaveCards())
        {
            try
            {
                $this->customer->createStripeCustomerIfNotExists(false, $order);
                $this->stripeCustomer = $this->customer->retrieveByStripeID();
                $params['customer'] = $this->stripeCustomer->id;
            }
            catch (\Stripe\Exception\CardException $e)
            {
                throw new LocalizedException(__($e->getMessage()));
            }
            catch (\Exception $e)
            {
                $this->helper->dieWithError(__('An error has occurred. Please contact us to complete your order.'), $e);
            }
        }
        else
        {
            $params['customer_email'] = $order->getCustomerEmail();
        }

        return $params;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $session = $this->checkoutHelper->getCheckout();
        $info = $this->getInfoInstance();
        $this->order = $order = $info->getOrder();
        $quote = $this->helper->getQuote();
        $this->reset();

        // We don't want to send an order email until the payment is collected asynchronously
        $order->setCanSendNewEmailFlag(false);

        $params = $this->getSessionParams($order);

        $this->adjustParamsForMethod($params, $info, $order, $quote);

        try {
            $checkoutSession = $this->config->getStripeClient()->checkout->sessions->create($params);
            $info->setAdditionalInformation("checkout_session_id", $checkoutSession->id);
            $session->setStripePaymentsCheckoutSessionId($checkoutSession->id);
            $order->addStatusHistoryComment(__("The customer was redirected for payment processing. The payment is pending."));
            $order->getPayment()
                ->setIsTransactionClosed(0)
                ->setIsTransactionPending(true);
        }
        catch (\Stripe\Exception\CardException $e)
        {
            throw new LocalizedException(__($e->getMessage()));
        }
        catch (\Exception $e)
        {
            if (strstr($e->getMessage(), 'Invalid country') !== false) {
                throw new LocalizedException(__('Sorry, this payment method is not available in your country.'));
            }
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$payment->getLastTransId())
            throw new LocalizedException(__('Sorry, it is not possible to invoice this order because the payment is still pending.'));

        try
        {
            $this->helper->capture($payment->getLastTransId(), $payment, $amount, $this->config->retryWithSavedCard());
        }
        catch (\Exception $e)
        {
            $this->helper->dieWithError($e->getMessage());
        }

        return parent::capture($payment, $amount);
    }

    public function cancel(InfoInterface $payment, $amount = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $helper = $objectManager->get('Magento\Payment\Helper\Data');
        $method = $helper->getMethodInstance('stripe_payments');

        $method->cancel($payment, $amount);

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

    // Fixes https://github.com/magento/magento2/issues/5413 in Magento 2.1
    public function setId($code) { }
    public function getId() { return $this->_code; }
}
