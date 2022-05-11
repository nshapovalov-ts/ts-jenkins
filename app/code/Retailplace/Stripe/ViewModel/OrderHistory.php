<?php

declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

namespace Retailplace\Stripe\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Retailplace\Stripe\Rewrite\Model\Method\Invoice;
use Mirakl\MMP\Common\Domain\Order\OrderState;

/**
 * Class OrderHistory
 */
class OrderHistory implements ArgumentInterface
{
    /**
     * State for order history grid
     *
     * @param Order $order
     * @param MiraklOrder $miraklOrder
     * @return string
     */
    public function getState($order, $miraklOrder)
    {
        $state = $miraklOrder->getStatus()->getState();
        if (strtolower($state) == strtolower('shipping')) {
            $state = "Order Preparation";
        }
        if ($miraklOrder->getPaymentType() == Invoice::METHOD_CODE && $state == OrderState::RECEIVED) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                switch ($invoice->getStripeInvoicePaid()) {
                    case Invoice::STRIPE_INVOICE_NOT_PAID:
                        $state = "Awaiting Payment";
                        break;
                    case Invoice::STRIPE_INVOICE_PAID:
                        $state = "Paid";
                        break;
                    case Invoice::STRIPE_INVOICE_NOT_PAID_ERROR:
                        $state = "Payment Failed";
                }
            }
        }

        return ucwords(strtolower($state));
    }
}
