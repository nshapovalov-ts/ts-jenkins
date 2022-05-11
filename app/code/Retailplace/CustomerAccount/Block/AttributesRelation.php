<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block;

use Amasty\CustomerAttributes\Model\ResourceModel\RelationDetails\CollectionFactory;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\SerializerInterface;

class AttributesRelation extends Template
{

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var CollectionFactory
     */
    private $relationCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * AttributesRelation constructor.
     * @param Template\Context $context
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param CollectionFactory $relationCollectionFactory
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        CollectionFactory $relationCollectionFactory,
        SerializerInterface $serializer,
        array $data = []
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getRelationData()
    {
        if ($this->getNameInLayout() == 'attribute_customer_edit') {
            $type = 'customer_account_edit';
        } else {
            $type = 'customer_attributes_registration';
        }

        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            $type
        );

        if (!$attributes || !$attributes->getSize()) {
            return [];
        }

        $attributeIds = $attributes->getColumnValues('attribute_id');
        if (empty($attributeIds)) {
            return [];
        }
        $dependentCollection = $this->relationCollectionFactory->create()
            ->addFieldToFilter('main_table.attribute_id', ['in' => $attributeIds])
            ->joinDependAttributeCode();
        $depends = [];
        foreach ($dependentCollection as $relationDetail) {
            $depends[] = [
                'parent_attribute_id' => $relationDetail->getAttributeId(),
                'parent_attribute_code' => $relationDetail->getData('parent_attribute_code'),
                'parent_attribute_element_uid' => $relationDetail->getData('parent_attribute_code'),
                'depend_attribute_element_uid' => $relationDetail->getData('dependent_attribute_code'),
                'parent_option_id' => $relationDetail->getOptionId(),
                'depend_attribute_id' => $relationDetail->getDependentAttributeId(),
                'depend_attribute_code' => $relationDetail->getData('dependent_attribute_code')
            ];
        }

        return $depends;
    }

    /**
     * Retrieve serialized relation data.
     *
     * @return bool|string
     */
    public function getSerializedRelationData()
    {
        return  $this->serializer->serialize($this->getRelationData());
    }
}
