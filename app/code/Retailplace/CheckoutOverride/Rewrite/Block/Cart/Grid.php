<?php

/**
 * Retailplace_CheckoutOverride
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

namespace Retailplace\CheckoutOverride\Rewrite\Block\Cart;

use Magento\Checkout\Block\Cart\Grid as CartGrid;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;

class Grid extends CartGrid
{

    /**
     * Get Seller Group Items
     *
     * @return array
     */
    public function getSellerGroupItems()
    {
        $shippingFees = $this->getQuote()->getMiraklShippingFees();
        $groupedOfferIds = [];
        $allItems = [];
        $mappingItemIdAndOfferId = [];
        $sellerGroupItems = [];
        $leadtimeToShip = [];

        if (!empty($shippingFees)) {
            /** @var OrderShippingFee $orderShippingFee */
            foreach ($shippingFees as $orderShippingFee) {
                $offers = $orderShippingFee->getOffers();
                if (empty($offers)) {
                    continue;
                }
                if ($offers) {
                    $groupedOfferIds[] = $offers->walk('getId');
                }
                foreach ($offers as $offer) {
                    $leadtimeToShip[$offer->getId()] = $orderShippingFee->getLeadtimeToShip();
                }
            }
        }

        foreach ($this->getItems() as $_item) {
            $allItems[$_item->getId()] = $_item;

            $offer = $_item->getOffer();
            if (!empty($offer)) {
                $mappingItemIdAndOfferId[$offer->getId()] = $_item->getId();
                $offer->setLeadtimeToShip(isset($leadtimeToShip[$offer->getId()]) ? $leadtimeToShip[$offer->getId()] : '');
            }
        }

        foreach ($groupedOfferIds as $key => $offerIds) {
            foreach ($offerIds as $offerId) {
                if (empty($mappingItemIdAndOfferId[$offerId])) {
                    continue;
                }

                $itemId = $mappingItemIdAndOfferId[$offerId];
                if (empty($allItems[$itemId])) {
                    continue;
                }

                $sellerGroupItems[$key][] = $allItems[$itemId];
                unset($allItems[$itemId]);
            }
        }

        if (!empty($allItems)) {
            foreach ($allItems as $_item) {
                $sellerGroupItems[$_item->getData('mirakl_shop_id')][] = $_item;
            }
        }

        return $sellerGroupItems;
    }
}
