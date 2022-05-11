<?php

namespace StripeIntegration\Payments\Model\Stripe;

class Invoice extends StripeObject
{
    protected $objectSpace = 'invoices';

    public function fromOrderItem($item, $order, $customerId, $stripeCoupon = null)
    {
        $daysDue = $order->getPayment()->getAdditionalInformation('days_due');

        $data = [
            'customer' => $customerId,
            'collection_method' => 'send_invoice',
            'description' => __("Order #%1 by %2", $order->getRealOrderId(), $order->getCustomerName()),
            'days_until_due' => $daysDue,
            'metadata' => [
                'Order #' => $order->getIncrementId()
            ]
        ];

        if (!empty($stripeCoupon->id))
        {
            $data['discounts'] = [[ 'coupon' => $stripeCoupon->id ]];
        }

        $this->createObject($data);

        if (!$this->object)
            throw new \Magento\Framework\Exception\LocalizedException(__("The invoice for order #%1 could not be created in Stripe", $order->getIncrementId()));

        return $this;
    }

    public function finalize()
    {
        $this->config->getStripeClient()->invoices->finalizeInvoice($this->getStripeObject()->id, []);

        return $this;
    }
}
