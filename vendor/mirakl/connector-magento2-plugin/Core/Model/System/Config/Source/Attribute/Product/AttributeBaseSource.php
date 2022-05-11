<?php
namespace Mirakl\Core\Model\System\Config\Source\Attribute\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

class AttributeBaseSource 
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attrCollectionFactory;

    /**
     * @param   AttributeCollectionFactory  $collectionFactory
     */
    public function __construct(AttributeCollectionFactory $collectionFactory)
    {
        $this->attrCollectionFactory = $collectionFactory;
    }

    /**
     * Retrieves all product attributes
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [[
            'value' => '',
            'label' => __('-- Please Select --'),
        ]];

        $collection = $this->attrCollectionFactory->create();
        $collection->addVisibleFilter()
            ->setOrder('frontend_label', 'ASC');

        foreach ($collection as $attribute) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            if ($attribute->getFrontendLabel()) {
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
                ];
            }
        }

        return $options;
    }
}
