<?php
namespace Retailplace\MiraklConnector\Rewrite\Helper;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\InputException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Mirakl\Connector\Helper\StockQty as MiraklStockQtyHelper;

class StockQty extends MiraklStockQtyHelper
{
    /**
     * Returns stock quantity of specified product according to
     * current website and potential multi inventory configuration.
     *
     * @param Product $product
     * @return  float
     */
    public function getProductStockQty(Product $product)
    {
        if (!$this->stockHelper->isMultiInventoryEnabled()) {
            return $this->stockHelper->getProductStockQty($product);
        }

        $products = [];
        if ($product->getTypeId() == Grouped::TYPE_CODE) {
            /** @var Grouped $typeInstance */
            $typeInstance = $product->getTypeInstance();
            $products = $typeInstance->getAssociatedProducts($product);
        } elseif ($product->getTypeId() == Configurable::TYPE_CODE) {
            /** @var Configurable $typeInstance */
            $typeInstance = $product->getTypeInstance();
            $products = $typeInstance->getUsedProductCollection($product);
            $products->getSelect()
                ->reset('columns')->columns('sku')
                ->where('link_table.parent_id = ?', $product->getId());
            $connection = $products->getConnection();
            $products = $connection->fetchCol($products->getSelect());

            //$products = $typeInstance->getUsedProductCollection($product);
        } else {
            $products[] = $product;
        }

        $qty = 0;
        $website = $product->getStore()->getWebsite();
        $stockId = $this->objectManager->get('Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite')
            ->execute($website->getCode());

        foreach ($products as $_product) {
            try {
                if (is_string($_product)) {
                    $sku = $_product;
                } else {
                    $sku = $_product->getSku();
                }
                $qty += $this->objectManager->get('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface')
                    ->execute($sku, $stockId);
            } catch (InputException $e) {
                // Ignore exception
            }
        }

        return $qty;
    }
}
