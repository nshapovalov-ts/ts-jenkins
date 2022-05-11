<?php
namespace Mirakl\Mcm\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Mcm\Helper\Data as McmHelper;
use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;

class AttributeUpdateBeforeObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $productIds = $observer->getEvent()->getData('product_ids');

        if (!$this->isEnabled() || empty($productIds)) {
            return;
        }

        $attributesData = $observer->getEvent()->getData('attributes_data');

        if (isset($attributesData['mirakl_sync'])) {
            // If mirakl_sync attribute has been modified, export products to Mirakl according to attribute value.
            if ($attributesData['mirakl_sync']) {
                if (isset($attributesData[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER])
                    && !$attributesData[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER]) {
                    return;
                }

                $collection = $this->getCollection($productIds);
                if (!isset($attributesData[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER])) {
                    $collection->addAttributeToFilter(McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER, 1);
                }
                $this->processHelper->exportCollection($collection, ProductAcceptance::STATUS_ACCEPTED, true);
            }
        } elseif (!empty($attributesData[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER])) {
            // If mirakl_mcm_is_operator_master attribute has been enabled, export products to Mirakl.
            $collection = $this->getCollection($productIds);
            $collection->addAttributeToFilter('mirakl_sync', 1);
            $this->processHelper->exportCollection($collection, ProductAcceptance::STATUS_ACCEPTED, true);
        } else {
            // Retrieve exportable attributes from MCI in order to compare them with provided attributes.
            // If any is shared, send products to Mirakl.
            $attrCodesToExport = $this->productHelper->getAttributeCodesToExport();
            $intersect = array_intersect(array_keys($attributesData), $attrCodesToExport);
            if (!empty($intersect)) {
                $collection = $this->getCollection($productIds);
                $collection->addAttributeToFilter('mirakl_sync', 1);
                $collection->addAttributeToFilter(McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER, 1);
                $this->processHelper->exportCollection($collection);
            }
        }
    }

    /**
     * @param   array   $productIds
     * @return  \Mirakl\Core\Model\ResourceModel\Product\Collection
     */
    private function getCollection(array $productIds)
    {
        /** @var \Mirakl\Core\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($productIds)
            ->addFieldToFilter('type_id', 'simple');

        return $collection;
    }
}
