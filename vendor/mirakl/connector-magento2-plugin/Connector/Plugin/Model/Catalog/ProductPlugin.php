<?php
namespace Mirakl\Connector\Plugin\Model\Catalog;

use Magento\Catalog\Model\Product;
use Mirakl\Connector\Helper\Offer as ConnectorOfferHelper;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\OfferFactory;

class ProductPlugin
{
    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var ConnectorOfferHelper
     */
    protected $connectorOfferHelper;

    /**
     * @param   OfferFactory            $offerFactory
     * @param   ConnectorOfferHelper    $offerHelper
     */
    public function __construct(
        OfferFactory $offerFactory,
        ConnectorOfferHelper $offerHelper
    ) {
        $this->offerFactory = $offerFactory;
        $this->connectorOfferHelper = $offerHelper;
    }

    /**
     * Return base price for Mirakl offer
     *
     * @param   Product     $product
     * @param   \Closure    $proceed
     * @return  float
     */
    public function aroundGetPrice(Product $product, \Closure $proceed)
    {
        if ($offer = $this->getOfferFromProduct($product)) {
            return $offer->getPrice();
        }

        return (float) $proceed();
    }

    /**
     * Returns final price for Mirakl offer with potential promotions
     *
     * @param   Product     $product
     * @param   \Closure    $proceed
     * @param   float|null  $qty
     * @return  float
     */
    public function aroundGetFinalPrice(Product $product, \Closure $proceed, $qty = null)
    {
        if ($offer = $this->getOfferFromProduct($product)) {
            return $this->connectorOfferHelper->getOfferFinalPrice($offer, $qty);
        }

        return $proceed($qty);
    }

    /**
     * @param   Product $product
     * @return  Offer|null
     */
    private function getOfferFromProduct(Product $product)
    {
        /** @var \Magento\Quote\Model\Quote\Item\Option $offerCustomOption */
        if ($product && ($offerCustomOption = $product->getCustomOption('mirakl_offer'))) {
            return $this->offerFactory->fromJson($offerCustomOption->getValue());
        }

        return null;
    }
}
