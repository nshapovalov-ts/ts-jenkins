<?php
namespace Mirakl\Connector\Plugin\Model\CatalogInventory;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockStateProvider;
use Mirakl\Connector\Helper\Offer as OfferHelper;

class StockStateProviderPlugin
{
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductResourceFactory
     */
    private $productResourceFactory;

    /**
     * @var OfferHelper
     */
    private $offerHelper;

    /**
     * @param   ProductFactory          $productFactory
     * @param   ProductResourceFactory  $productResourceFactory
     * @param   OfferHelper             $offerHelper
     */
    public function __construct(
        ProductFactory $productFactory,
        ProductResourceFactory $productResourceFactory,
        OfferHelper $offerHelper
    ) {
        $this->productFactory = $productFactory;
        $this->productResourceFactory = $productResourceFactory;
        $this->offerHelper = $offerHelper;
    }

    /**
     * Check if product has Mirakl offer used when a product is saved to check stock availability
     *
     * @param   StockStateProvider  $subject
     * @param   \Closure            $proceed
     * @param   StockItemInterface  $stockItem
     * @return  bool
     */
    public function aroundVerifyStock(StockStateProvider $subject, \Closure $proceed, StockItemInterface $stockItem)
    {
        if ($proceed($stockItem)) {
            return true;
        }

        return $this->verifyOffersStock($stockItem);
    }

    /**
     * Verify if specified stock item has Mirakl offers in stock
     *
     * @param   StockItemInterface  $stockItem
     * @return  bool
     */
    private function verifyOffersStock(StockItemInterface $stockItem)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->productFactory->create();
        $this->productResourceFactory->create()->load($product, $stockItem->getProductId());

        return $product->getId() && $this->offerHelper->hasAvailableOffersForProduct($product);
    }
}
