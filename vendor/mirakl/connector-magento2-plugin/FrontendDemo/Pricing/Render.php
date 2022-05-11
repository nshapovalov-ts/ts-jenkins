<?php
namespace Mirakl\FrontendDemo\Pricing;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Pricing\Render as BaseCatalogPricingRender;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;

/**
 * @method  string  getPriceRender()
 * @method  string  getPriceTypeCode()
 */
class Render extends BaseCatalogPricingRender
{
    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @param   Context         $context
     * @param   Registry        $registry
     * @param   OfferHelper     $frontendOfferHelper
     * @param   ProductFactory  $productFactory
     * @param   ConnectorConfig $connectorConfig
     * @param   array           $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        OfferHelper $frontendOfferHelper,
        ProductFactory $productFactory,
        ConnectorConfig $connectorConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);

        $this->offerHelper     = $frontendOfferHelper;
        $this->productFactory  = $productFactory;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * Returns saleable item instance
     *
     * @return  Product
     */
    protected function getProduct()
    {
        $product = parent::getProduct();

        $operatorOffer = $this->offerHelper->getBestOperatorOffer($product);
        if ($operatorOffer) {
            return $product;
        }

        $offer = $this->offerHelper->getBestOffer($product);
        if (!$offer) {
            return $product;
        }

        $renderProduct = $this->productFactory->create();
        $renderProduct->setId($product->getId());
        $renderProduct->setSku($offer->getProductSku());
        $renderProduct->setPrice($offer->getPrice());
        $renderProduct->setQty($offer->getQuantity());
        $renderProduct->setTaxClassId($product->getTaxClassId());
        if ($offer->getOriginPrice() > $offer->getPrice()) {
            $renderProduct->setSpecialPrice($offer->getPrice());
            $renderProduct->setPrice($offer->getOriginPrice());
        }
        $renderProduct->setData('main_offer', $offer);

        return $renderProduct;
    }
}
