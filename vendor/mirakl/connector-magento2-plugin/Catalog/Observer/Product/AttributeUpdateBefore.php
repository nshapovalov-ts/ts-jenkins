<?php
namespace Mirakl\Catalog\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Catalog\Model\Product\Attribute\MassUpdate;

class AttributeUpdateBefore extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();

        if (!$this->isEnabled() || empty($productIds)) {
            return;
        }

        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addIdFilter($productIds);
        $collection->addCategoryIds();

        $attributesData = $observer->getEvent()->getAttributesData();

        if (isset($attributesData['mirakl_sync'])) {
            $action = $attributesData['mirakl_sync'] ? 'update' : 'delete';
            $massUpdate = new MassUpdate([
                'action'     => $action,
                'collection' => $collection
            ]);
            $this->registry->register('mass_update_mirakl_p21_collection', $massUpdate, true);
        } elseif (
            isset($attributesData['description']) ||
            isset($attributesData['mirakl_category_id']) ||
            isset($attributesData['name']) ||
            isset($attributesData['mirakl_authorized_shop_ids']) ||
            isset($attributesData['status']) ||
            isset($attributesData[$this->catalogConfigHelper->getBrandAttributeCode()])
        ) {
            $collection->addAttributeToFilter('mirakl_sync', 1);
            $massUpdate = new MassUpdate([
                'action'     => 'update',
                'collection' => $collection,
            ]);
            $this->registry->register('mass_update_mirakl_p21_collection', $massUpdate, true);
        }
    }
}
