<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;

/**
 * Class QuoteSubmitBefore
 */
class QuoteSubmitBefore implements ObserverInterface
{
    /**
     * Copy Mirakl Quote ID to Order
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        $order = $observer->getData('order');

        $order->setData(
            MiraklQuoteAttributes::MIRAKL_ORDER_QUOTE_ID,
            $quote->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)
        );
    }
}
