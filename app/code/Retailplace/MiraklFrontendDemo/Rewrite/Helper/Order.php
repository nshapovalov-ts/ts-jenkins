<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Helper;

use Exception;
use Mirakl\Connector\Helper\Order as HelperOrder;
use Magento\Sales\Model\Order as OrderModel;
use Mirakl\MMP\Front\Domain\Order\Create\CreatedOrders;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\MMP\FrontOperator\Domain\Order\OrderLine;
use Magento\Sales\Model\Order\Item;

class Order extends HelperOrder
{

    /**
     * Returns Mirakl order total of specified order item fields
     *
     * @param OrderModel $order
     * @param MiraklOrder $miraklOrder
     * @param array $orderItemFields
     * @return  float
     */
    private function getMiraklTotal(OrderModel $order, MiraklOrder $miraklOrder, array $orderItemFields): float
    {
        $total = 0;

        foreach ($order->getItemsCollection() as $item) {
            /** @var Item $item */
            if ($item->getMiraklOfferId()) {
                /** @var OrderLine $orderLine */
                foreach ($miraklOrder->getOrderLines() as $orderLine) {
                    if ($orderLine->getOffer() && $orderLine->getOffer()->getId() == $item->getMiraklOfferId()) {
                        foreach ($orderItemFields as $field) {
                            $total += $item->getData($field);
                        }
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Returns shipping tax amount of specified Magento order including only Mirakl items
     *
     * @param OrderModel $order
     * @param MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklShippingTaxAmount(OrderModel $order, MiraklOrder $miraklOrder): float
    {
        return $this->getMiraklTotal($order, $miraklOrder, [
            'mirakl_shipping_tax_amount',
            'mirakl_custom_shipping_tax_amount'
        ]);
    }

    /**
     * Creates Mirakl order and set Magento order as sent if creation succeeded
     *
     * @param   OrderModel  $order
     * @param   bool        $markAsSent
     * @param   bool        $useQueue
     * @return  CreatedOrders
     * @throws  Exception
     */
    public function createMiraklOrder(
        OrderModel $order,
        $markAsSent = true,
        bool $useQueue = true
    ) {
        $createdOffers = $this->orderApiHelper->createMiraklOrder((int) $order->getId(), $markAsSent, $useQueue);

        return $createdOffers;
    }
}
