<?php
namespace Mirakl\FrontendDemo\Block\Product;

use Magento\Catalog\Block\Product\View as BaseView;
use Mirakl\Connector\Model\Offer;

class View extends BaseView
{
    use OfferQuantityTrait;

    /**
     * {@inheritdoc}
     */
    public function getProductDefaultQty($product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        /** @var Offer $offer */
        if ($offer = $product->getData('main_offer')) {
            return $this->getOfferDefaultQty($offer);
        }

        return parent::getProductDefaultQty($product);
    }

    /**
     * @return array
     */
    public function getMixedQuantityValidators()
    {
        /** @var Offer $offer */
        $offer = $this->getProduct()->getData('main_offer');

        return $offer ? $this->getOfferQuantityValidators($offer) : $this->getQuantityValidators();
    }
}