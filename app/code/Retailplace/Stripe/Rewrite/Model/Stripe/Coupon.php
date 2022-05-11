<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model\Stripe;

use StripeIntegration\Payments\Model\Stripe\Coupon as StripeCoupon;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Coupon
 */
class Coupon extends StripeCoupon
{
    protected $objectSpace = 'coupons';

    /**
     * Get Basic Coupon
     *
     * @param string|float|null $amount
     * @param string|null $name
     * @param string $currency
     * @param string $duration
     * @return Coupon|null
     * @throws LocalizedException
     */
    public function getBasicCoupon(
        $amount,
        ?string $name,
        string $currency = "",
        string $duration = "forever"
    ): ?Coupon {
        $name = !empty($name) ? $name : $this->helper->addCurrencySymbol($amount, $currency) . " Discount";

        $stripeAmount = $this->helper->convertMagentoAmountToStripeAmount($amount, $currency);

        $couponId = ((string) $stripeAmount) . strtoupper($currency);

        $data = [
            'id'         => $couponId,
            'amount_off' => $stripeAmount,
            'currency'   => $currency,
            'name'       => $name,
            'duration'   => $duration
        ];

        $this->getObject($data['id']);

        if (!$this->object) {
            $this->createObject($data);
        }

        if (!$this->object) {
            throw new LocalizedException(
                __("The discount for %1 could not be created in Stripe", $data['id'])
            );
        }

        return $this;
    }
}
