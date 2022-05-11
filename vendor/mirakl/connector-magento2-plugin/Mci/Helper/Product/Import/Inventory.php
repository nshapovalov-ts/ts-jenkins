<?php
namespace Mirakl\Mci\Helper\Product\Import;

use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManagerInterface;
use Mirakl\Connector\Helper\Stock;

class Inventory
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
     * @param   ObjectManagerInterface  $objectManager
     * @param   Stock                   $stockHelper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Stock $stockHelper
    ) {
        $this->objectManager = $objectManager;
        $this->stockHelper = $stockHelper;
    }

    /**
     * @param Product $product
     */
    public function createSourceItems(Product $product)
    {
        if (!$this->stockHelper->isMultiInventoryEnabled()) {
            return;
        }

        $sourceItems = [];

        foreach ($this->getSources() as $source) {
            /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem */
            $sourceItemFactory = $this->objectManager->get('Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory');
            $sourceItem = $sourceItemFactory->create();
            $sourceItem->setSku($product->getSku());
            $sourceItem->setSourceCode($source->getSourceCode());
            $sourceItem->setQuantity(0);
            $sourceItem->setStatus(0);
            $sourceItems[] = $sourceItem;
        }

        if (count($sourceItems) > 0) {
            /** @var \Magento\InventoryApi\Api\SourceItemsSaveInterface $sourceItemsSave */
            $sourceItemsSave = $this->objectManager->get('Magento\InventoryApi\Api\SourceItemsSaveInterface');
            $sourceItemsSave->execute($sourceItems);
        }
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\SourceInterface[]
     */
    protected function getSources()
    {
        /** @var \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository */
        $sourceRepository = $this->objectManager->get('Magento\InventoryApi\Api\SourceRepositoryInterface');

        return $sourceRepository->getList()->getItems();
    }
}
