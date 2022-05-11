<?php

/**
 * Retailplace_CheckoutOverride
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CheckoutOverride\Block\Cart\Item;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\FrontendDemo\Block\Cart\Item\Shipping as MiraklShipping;

/**
 * Class Shipping
 */
class Shipping extends MiraklShipping
{
    /**
     * Get quote item
     *
     * @return QuoteItem
     */
    public function getItem()
    {
        $item = $this->getParentBlock()->getItem();
        $this->setItem($item);
        if ($this->_item && $this->_item->getShowShopname()) {
            $this->_item->setShop($this->getShop());
        }

        return $this->_item;
    }

    /**
     * Get Offer from the Quote Item
     *
     * @return  \Mirakl\Connector\Model\Offer|null
     */
    public function getOffer()
    {
        $item = $this->_item;
        $result = null;

        if ($item) {
            /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
            $offerCustomOption = $item->getProduct()->getCustomOption('mirakl_offer');
            if ($offerCustomOption) {
                $result = $this->offerFactory->fromJson($offerCustomOption->getValue());
            }
        }

        return $result;
    }

    /**
     * Get Formatted Min Quote Request Remaining Amount
     *
     * @return string
     */
    public function getMinQuoteAmountRemaining(): string
    {
        /** @var \Retailplace\MiraklShop\Api\Data\ShopInterface $shop */
        $shop = $this->getShop();

        return (string) $this->priceCurrency->format(
            $shop->getShopAmounts()->getMinQuoteAmountRemaining(),
            false
        );
    }

    /**
     * Get Block Html
     *
     * @return string
     */
    protected function _toHtml()
    {
        $result = '';
        if ($this->getItem()) {
            $result = parent::_toHtml();
        }

        return $result;
    }
}
