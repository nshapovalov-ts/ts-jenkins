<?php
namespace Mirakl\Core\Model\System\Config\Source\Attribute\Product;

class Identifier extends AttributeBaseSource
{
    /**
     * Retrieves all product attributes in order to choose potential identifier attributes in configuration
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];

        $collection = $this->attrCollectionFactory->create();
        $collection->addVisibleFilter()
            ->addIsUniqueFilter()
            ->setOrder('frontend_label', 'ASC');

        foreach ($collection as $attribute) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attrCode = $attribute->getAttributeCode();
            if ($attribute->isScopeGlobal() && $attribute->getFrontendLabel() && $attrCode != 'sku') {
                $options[] = [
                    'value' => $attrCode,
                    'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attrCode),
                ];
            }
        }

        return $options;
    }
}
