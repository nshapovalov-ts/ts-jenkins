<?php
namespace Mirakl\Connector\Helper;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class StockQty extends AbstractHelper
{
    /**
     * Need to use object manager in order to keep compatibility
     * with version 2.2.x of Magento
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Stock
     */
    protected $stockHelper;

    /**
     * @param   Context                 $context
     * @param   ObjectManagerInterface  $objectManager
     * @param   Stock                   $stockHelper
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        Stock $stockHelper
    ) {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->stockHelper = $stockHelper;
    }

    /**
     * Returns stock quantity of specified product according to
     * current website and potential multi inventory configuration.
     *
     * @param   Product $product
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
        } else {
            $products[] = $product;
        }

        $qty = 0;
        $website = $product->getStore()->getWebsite();
        $stockId = $this->objectManager->get('Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite')
            ->execute($website->getCode());

        foreach ($products as $_product) {
            try {
                $qty += $this->objectManager->get('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface')
                    ->execute($_product->getSku(), $stockId);
            } catch (InputException $e) {
                // Ignore exception
            }
        }

        return $qty;
    }
}