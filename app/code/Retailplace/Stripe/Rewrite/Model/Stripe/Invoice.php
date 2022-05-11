<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model\Stripe;

use StripeIntegration\Payments\Model\Stripe\Invoice as StripeInvoice;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Invoice
 */
class Invoice extends StripeInvoice
{

    /**
     * From Order Item
     *
     * @param $item
     * @param $order
     * @param $customerId
     * @param null $stripeCoupons
     * @return $this
     * @throws LocalizedException
     */
    public function fromOrderItem($item, $order, $customerId, $stripeCoupons = null): Invoice
    {
        $daysDue = $order->getPayment()->getAdditionalInformation('days_due');

        $data = [
            'customer'               => $customerId,
            'collection_method'      => 'send_invoice',
            'description'            => __("Order #%1 by %2", $order->getRealOrderId(), $order->getCustomerName()),
            'days_until_due'         => $daysDue,
            'metadata'               => [
                'Order #' => $order->getIncrementId()
            ],
            'auto_advance'           => true,
            'payment_settings'       => $order->getPayment()->getAdditionalInformation('payment_settings'),
            'default_payment_method' => $order->getPayment()->getAdditionalInformation('token')
        ];

        if (!is_array($stripeCoupons) && !empty($stripeCoupons->id)) {
            $data['discounts'] = [['coupon' => $stripeCoupons->id]];
        } elseif (!empty($stripeCoupons) && is_array($stripeCoupons)) {
            foreach ($stripeCoupons as $stripeCoupon) {
                $data['discounts'][] = ['coupon' => $stripeCoupon->id];
            }
        }

        $this->createObject($data);

        if (!$this->object) {
            throw new LocalizedException(
                __("The invoice for order #%1 could not be created in Stripe", $order->getIncrementId())
            );
        }

        return $this;
    }
}
