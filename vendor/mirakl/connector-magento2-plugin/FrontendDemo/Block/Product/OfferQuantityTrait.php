<?php
namespace Mirakl\FrontendDemo\Block\Product;

use Mirakl\Connector\Model\Offer;

trait OfferQuantityTrait
{
    /**
     * @param   Offer   $offer
     * @return  int
     */
    public function getOfferDefaultQty($offer)
    {
        return max(1, $offer->getMinOrderQuantity(), $offer->getPackageQuantity());
    }

    /**
     * @param   Offer   $offer
     * @return  array
     */
    public function getOfferQuantityValidators($offer)
    {
        $validators = ['required-number' => true];

        $params = [];
        $params['minAllowed'] = max(1, $offer->getMinOrderQuantity());
        if ($offer->getMaxOrderQuantity()) {
            $params['maxAllowed'] = $offer->getMaxOrderQuantity();
        }
        if ($offer->getPackageQuantity() > 0) {
            $params['qtyIncrements'] = $offer->getPackageQuantity();
        }
        $validators['validate-item-quantity'] = $params;

        return $validators;
    }
}