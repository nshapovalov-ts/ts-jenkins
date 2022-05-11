<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

namespace Amasty\CustomerAttributes\Plugin\Eav\Model\Validator;

use Amasty\CustomerAttributes\Model\Attribute;

class Data
{
    /**
     * @var \Magento\Customer\Model\AttributeMetadataDataProvider
     */
    protected $attributeMetadataDataProvider;

    public function __construct(
        \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
    }

    /**
     * @param \Magento\Eav\Model\Validator\Attribute\Data $subject
     * @param $entity
     */
    public function beforeIsValid(
        \Magento\Eav\Model\Validator\Attribute\Data $subject,
        $entity
    ) {
        $blacklist = [];
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            Attribute::AMASTY_ATTRIBUTE_CODE
        );

        foreach ($attributes as $attribute) {
            $blacklist[] = $attribute->getAttributeCode();
        }
        $subject->setAttributesBlackList($blacklist);
    }
}
