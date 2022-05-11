<?php
namespace Mirakl\Connector\Helper;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Stock extends AbstractHelper
{
    /**
     * @var StockRegistryProviderInterface
     */
    protected $stockRegistryProvider;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @param   Context                         $context
     * @param   StockRegistryProviderInterface  $stockRegistryProvider
     * @param   StockConfigurationInterface     $stockConfiguration
     */
    public function __construct(
        Context $context,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockConfigurationInterface $stockConfiguration
    ) {
        parent::__construct($context);
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Returns stock quantity of specified product.
     * Rewriting the default Magento method because it does not work for configurable product (returns 0).
     * @see \Magento\CatalogInventory\Model\StockStateProvider::getStockQty()
     *
     * @param   Product $product
     * @return  float
     */
    public function getProductStockQty(Product $product)
    {
        if (!$product->hasStockQty()) {
            $product->setStockQty(0); // prevent possible recursive loop
            $stockItem = $this->getProductStockItem($product);
            if (!$product->isComposite()) {
                $stockQty = $stockItem->getQty();
            } else {
                $stockQty = null;
                $productsByGroups = $product->getTypeInstance()->getProductsToPurchaseByReqGroups($product);
                foreach ($productsByGroups as $productsInGroup) {
                    $qty = 0;
                    foreach ($productsInGroup as $childProduct) {
                        $qty += $this->getProductStockQty($childProduct);
                    }
                    if (null === $stockQty || $qty < $stockQty) {
                        $stockQty = $qty;
                    }
                }
            }
            $stockQty = (float) $stockQty;
            if ($stockQty < 0 || !$stockItem->getManageStock() || !$stockItem->getIsInStock()
                || !$product->isSaleable()
            ) {
                $stockQty = 0;
            }
            $product->setStockQty($stockQty);
        }

        return $product->getStockQty();
    }

    /**
     * @param   Product $product
     * @return  StockItemInterface
     */
    public function getProductStockItem(Product $product)
    {
        $scopeId = $this->stockConfiguration->getDefaultScopeId();

        return $this->stockRegistryProvider->getStockItem($product->getId(), $scopeId);
    }

    /**
     * @return bool
     */
    public function isMultiInventoryEnabled()
    {
        return $this->_moduleManager->isEnabled('Magento_Inventory');
    }
}
