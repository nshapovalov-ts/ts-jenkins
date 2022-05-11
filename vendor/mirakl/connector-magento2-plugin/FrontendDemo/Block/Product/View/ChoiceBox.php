<?php
namespace Mirakl\FrontendDemo\Block\Product\View;

use Mirakl\Connector\Model\Offer;
use Mirakl\FrontendDemo\Block\Product\View\Tab\Offers;

class ChoiceBox extends Offers
{
    /**
     * Get all offers
     *
     * @param   int|array   $excludeOfferIds
     * @return  array
     */
    public function getAllOffers($excludeOfferIds = null)
    {
        $resultOffers = [];
        if ($this->isEnabled()) {
            $offers = parent::getAllOffers($excludeOfferIds);

            /** @var Offer $offer */
            foreach ($offers as $offer) {
                if (!isset($resultOffers['min_price']) || $offer->getPrice() < $resultOffers['min_price']) {
                    $resultOffers['min_price'] = $offer->getPrice();
                }
            }

            $resultOffers['offers'] = $offers;
            $resultOffers['total_count'] = count($offers);

            if ($this->offerHelper->isOperatorProductAvailable($this->getProduct())) {
                // +1 : we add the operator offer or shop offer (id excluded) present in buy box
                $resultOffers['total_count'] += 1;
            }

            $resultOffers['max_count'] = $this->configHelper->getNbChoiceBoxElements();
        }

        return $resultOffers;
    }

    /**
     * @return  bool
     */
    public function isEnabled()
    {
        return $this->configHelper->isChoiceBoxEnabled();
    }
}
