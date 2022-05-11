<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Plugin;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemOrigin;
use Magento\Sales\Api\Data\OrderItemInterface;
use Retailplace\MiraklPromotion\Model\PromotionManagement;

/**
 * ToOrderItem class
 */
class ToOrderItem
{
    /**
     * Transfer mirakl promotion data from quote item to order item
     *
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param \Magento\Sales\Api\Data\OrderItemInterface $result
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function afterConvert(
        ToOrderItemOrigin $subject,
        OrderItemInterface $result,
        AbstractItem $item
    ) {
        $result->setData(
            PromotionManagement::ORDER_MIRAKL_PROMOTION_DEDUCED_AMOUNT,
            $item->getData(PromotionManagement::QUOTE_MIRAKL_PROMOTION_DEDUCED_AMOUNT)
        );
        $result->setData(
            PromotionManagement::ORDER_MIRAKL_PROMOTION_DATA,
            $item->getData(PromotionManagement::QUOTE_MIRAKL_PROMOTION_DATA)
        );

        return $result;
    }
}
