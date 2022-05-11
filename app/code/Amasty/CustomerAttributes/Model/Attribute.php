<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Model;

use Amasty\CustomerAttributes\Model\ResourceModel\RelationDetails\CollectionFactory;

class Attribute
{
    const AMASTY_ATTRIBUTE_CODE = 'amasty_custom_attribute';

    /**
     * @var \Magento\Customer\Model\AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var CollectionFactory
     */
    private $relationCollectionFactory;

    /**
     * @var array
     */
    private $ourAttributeCodes;

    /**
     * @var array
     */
    private $ourAttributeDetails;

    /**
     * @var array
     */
    private $ourAttributesCache;

    /**
     * Attribute constructor.
     * @param \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider
     */
    public function __construct(
        \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider,
        CollectionFactory $relationDetailsCollectionFactory
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->relationCollectionFactory = $relationDetailsCollectionFactory;
    }

    /**
     * Check if it's attribute is Amasty custom attribute
     *
     * @param string $attributeCode
     * @return bool
     */
    public function isOurAttribute($attributeCode)
    {
        $isOurAttribute = false;

        if (!isset($this->ourAttributeCodes)) {
            $attributesMetaCollection = $this->attributeMetadataDataProvider
                ->loadAttributesCollection(
                    'customer',
                    self::AMASTY_ATTRIBUTE_CODE
                );
            $attributesMetaCollection
                ->join(
                    ['eav_attribute' => $attributesMetaCollection->getTable('eav_attribute')],
                    'main_table.attribute_id = eav_attribute.attribute_id',
                    ['attribute_code']
                )
                ->removeAllFieldsFromSelect();
            $this->ourAttributeCodes = $attributesMetaCollection->getColumnValues('attribute_code');
        }

        if (in_array($attributeCode, $this->ourAttributeCodes)) {
            $isOurAttribute = true;
        }

        return $isOurAttribute;
    }

    /**
     * Get information about our attributes
     *
     * @param $object
     * @return array
     */
    public function getAttributeDetails($object)
    {
        if (isset($this->ourAttributeDetails)) {
            return $this->ourAttributeDetails;
        }

        $this->ourAttributeDetails = [];
        $ourAttributes = $this->extractCustomerAttributeCodes($object);

        if (empty($ourAttributes)) {
            return $this->ourAttributeDetails;
        }

        $attributeIds = [];

        foreach ($ourAttributes as $attribute) {
            $attributeIds[] = $attribute->getId();
        }

        $relationCollection = $this->relationCollectionFactory->create()
            ->addFieldToFilter('main_table.attribute_id', ['in' => $attributeIds]);

        /** @var \Amasty\CustomerAttributes\Model\RelationDetails $item */
        foreach ($relationCollection as $item) {
            $attributeId = $item->getAttributeId();
            $dependentAttributeId = $item->getDependentAttributeId();
            $this->ourAttributeDetails[$attributeId]['option_id'] = $item->getOptionId();

            if (empty($this->ourAttributeDetails[$dependentAttributeId]['parent_attributes']) ||
                !in_array($dependentAttributeId, $this->ourAttributeDetails[$dependentAttributeId]['parent_attributes'])
            ) {
                $this->ourAttributeDetails[$dependentAttributeId]['parent_attributes'][] = $attributeId;
            }
        }

        return $this->ourAttributeDetails;
    }

    /**
     * Get a list of objects of our attributes
     *
     * @param $object
     * @return array
     */
    public function extractCustomerAttributeCodes($object)
    {
        if (isset($this->ourAttributesCache)) {
            return $this->ourAttributesCache;
        }

        $this->ourAttributesCache = [];

        if (!$object instanceof \Magento\Customer\Model\Customer) {
            return $this->ourAttributesCache;
        }

        $attributes = $object->getAttributes();

        foreach ($attributes as $attribute) {
            if ($this->isOurAttribute($attribute->getAttributeCode())) {
                $this->ourAttributesCache[$attribute->getId()] = $attribute;
            }
        }

        ksort($this->ourAttributesCache);

        return $this->ourAttributesCache;
    }
}
