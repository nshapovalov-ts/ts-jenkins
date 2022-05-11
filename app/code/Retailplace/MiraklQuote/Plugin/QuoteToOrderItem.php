<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Plugin;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;

/**
 * Class QuoteToOrderItem
 */
class QuoteToOrderItem
{
    /**
     * Add Mirakl Quote Items data to Order Items
     *
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param \Magento\Sales\Api\Data\OrderItemInterface $orderItem
     * @param AbstractItem $item
     * @param array $data
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function afterConvert(ToOrderItem $subject, OrderItemInterface $orderItem, AbstractItem $item, $data = []): OrderItemInterface
    {
        $orderItem->setData(
            MiraklQuoteAttributes::MIRAKL_ORDER_ITEM_ID,
            $item->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ITEM_ID)
        );

        return $orderItem;
    }
}
