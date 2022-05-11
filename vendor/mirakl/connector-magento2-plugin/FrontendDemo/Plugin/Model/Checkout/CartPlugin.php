<?php
namespace Mirakl\FrontendDemo\Plugin\Model\Checkout;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Checkout\Model\Cart;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;

class CartPlugin
{
    /**
     * @var StockStateInterface
     */
    private $stockState;

    /**
     * @var OfferFactory
     */
    private $offerFactory;

    /**
     * @param   StockStateInterface $stockState
     * @param   OfferFactory        $offerFactory
     */
    public function __construct(StockStateInterface $stockState, OfferFactory $offerFactory)
    {
        $this->stockState = $stockState;
        $this->offerFactory = $offerFactory;
    }

    /**
     * Check offer suggested qty if quote item refer to an offer
     *
     * @param   Cart        $subject
     * @param   \Closure    $proceed
     * @param   array       $data
     * @return  array
     */
    public function aroundSuggestItemsQty(Cart $subject, \Closure $proceed, $data)
    {
        foreach ($data as $itemId => $itemInfo) {
            if (!isset($itemInfo['qty'])) {
                continue;
            }

            $qty = (float) $itemInfo['qty'];
            if ($qty <= 0) {
                continue;
            }

            $quoteItem = $subject->getQuote()->getItemById($itemId);
            if (!$quoteItem) {
                continue;
            }

            $getMiraklOffer = function (Product $product) {
                return $product->getCustomOption('mirakl_offer');
            };

            /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
            $offerCustomOption = $getMiraklOffer($quoteItem->getProduct());

            if (!$offerCustomOption) {
                /** @var \Magento\Quote\Model\Quote\Item $parentItem */
                if ($parentItem = $quoteItem->getParentItem()) {
                    $offerCustomOption = $getMiraklOffer($parentItem->getProduct());
                }
            }

            if ($offerCustomOption) {
                // Quote item is associated to a Mirakl offer, override stock item order conditions
                $offer = $this->offerFactory->fromJson($offerCustomOption->getValue());

                if ($offer) {
                    $data[$itemId]['before_suggest_qty'] = $qty;
                    $data[$itemId]['qty'] = $this->suggestQty($offer, $qty);
                }

                continue;
            }

            $product = $quoteItem->getProduct();
            if (!$product) {
                continue;
            }

            $data[$itemId]['before_suggest_qty'] = $qty;
            $data[$itemId]['qty'] = $this->stockState->suggestQty(
                $product->getId(),
                $qty,
                $product->getStore()->getWebsiteId()
            );
        }

        return $data;
    }

    /**
     * Returns suggested qty that satisfies qty increments and minQty/maxQty conditions
     * or original qty if such value does not exist
     *
     * @param  Offer      $offer
     * @param  int|float  $qty
     * @return int|float
     */
    protected function suggestQty(Offer $offer, $qty)
    {
        // We do not manage stock
        if ($qty <= 0) {
            return $qty;
        }

        $qtyIncrements = (int) $offer->getPackageQuantity();

        if ($qtyIncrements < 2) {
            return $qty;
        }

        $minQty       = max($offer->getMinOrderQuantity(), $qtyIncrements);
        $divisibleMin = ceil($minQty / $qtyIncrements) * $qtyIncrements;

        $maxQty       = min($offer->getQty(), $offer->getMaxOrderQuantity());
        $divisibleMax = floor($maxQty / $qtyIncrements) * $qtyIncrements;

        if ($qty < $minQty || $qty > $maxQty || $divisibleMin > $divisibleMax) {
            // Do not perform rounding for qty that does not satisfy min/max conditions to not confuse customer
            return $qty;
        }

        // Suggest value closest to given qty
        $closestDivisibleLeft  = floor($qty / $qtyIncrements) * $qtyIncrements;
        $closestDivisibleRight = $closestDivisibleLeft + $qtyIncrements;
        $acceptableLeft        = min(max($divisibleMin, $closestDivisibleLeft), $divisibleMax);
        $acceptableRight       = max(min($divisibleMax, $closestDivisibleRight), $divisibleMin);

        return abs($acceptableLeft - $qty) < abs($acceptableRight - $qty) ? $acceptableLeft : $acceptableRight;
    }
}
