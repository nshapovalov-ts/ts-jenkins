<?php

namespace StripeIntegration\Payments\Model\Stripe;

class Coupon extends StripeObject
{
    protected $objectSpace = 'coupons';
    public $rule = null;
    public $coupon = null;

    public function fromOrder($order)
    {
        $currency = $order->getOrderCurrencyCode();
        $amount = abs($order->getDiscountAmount());
        $data = $this->getCouponParams($amount, $currency, $order);

        if (!$data)
            return $this;

        $this->getObject($data['id']);

        if (!$this->object)
            $this->createObject($data);

        if (!$this->object)
            throw new \Magento\Framework\Exception\LocalizedException(__("The discount for order #%1 could not be created in Stripe", $order->getIncrementId()));

        return $this;
    }

    public function fromOrderItem($order, $orderItem)
    {
        $currency = $order->getOrderCurrencyCode();
        $amount = abs($orderItem->getDiscountAmount());
        $data = $this->getCouponParams($amount, $currency, $order);

        if (!$data)
            return $this;

        $this->getObject($data['id']);

        if (!$this->object)
            $this->createObject($data);

        if (!$this->object)
            throw new \Magento\Framework\Exception\LocalizedException(__("The discount for %1 could not be created in Stripe", $orderItem->getName()));

        return $this;
    }

    public function getCouponExpirationParams($ruleId)
    {
        $defaults = ['duration' => 'forever'];

        if (empty($ruleId))
            return $defaults;

        $coupon = $this->helper->loadStripeCouponByRuleId($ruleId);
        $duration = $coupon->duration();
        $months = $coupon->months();

        if ($months && $months > 0)
        {
            return [
                'duration' => $duration,
                'duration_in_months' => $months
            ];
        }

        return ['duration' => $duration];
    }

    public function getCouponParams($amount, $currency, $order)
    {
        $couponCode = $order->getCouponCode();

        if (empty($amount) || empty($couponCode))
            return null;

        $this->coupon = $coupon = $this->helper->loadCouponByCouponCode($couponCode);
        if (!$coupon->getRuleId())
            return null;

        $this->rule = $rule = $this->helper->loadRuleByRuleId($coupon->getRuleId());
        $action = $rule->getSimpleAction();
        if (empty($action))
            return null;

        if (!$this->helper->hasSubscriptionsIn($order->getAllItems()))
            $action = "by_fixed";

        switch ($action)
        {
            case 'by_percent':
                $discountType = "percent_off";
                $stripeAmount = (float)$rule->getDiscountAmount();
                $couponId = ((string)$stripeAmount) . "percent";
                $name = $stripeAmount . "% Discount";
                break;
            case 'by_fixed':
                $discountType = "amount_off";
                $stripeAmount = $this->helper->convertMagentoAmountToStripeAmount($amount, $currency);
                $couponId = ((string)$stripeAmount) . strtoupper($currency);
                $name = $this->helper->addCurrencySymbol($amount, $currency) . " Discount";
                break;
            case 'cart_fixed':
            case 'buy_x_get_y':
            default:
                throw new LocalizedException(__("This discount coupon cannot be applied on this order. Please remove the coupon and try again (err: 0)"));
        }

        $expirationParams = $this->getCouponExpirationParams($coupon->getRuleId());
        if ($expirationParams['duration'] != 'forever')
            $couponId .= "-" . $expirationParams['duration'];

        if (isset($expirationParams['duration_in_months']))
            $couponId .= "-" . $expirationParams['duration_in_months'];

        $params = [
            'id' => $couponId,
            $discountType => $stripeAmount,
            'currency' => $currency,
            'name' => $name
        ];

        $params = array_merge($params, $expirationParams);

        return $params;
    }
}
