<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

namespace Amasty\CustomerAttributes\Plugin\Customer\Model;

use Amasty\CustomerAttributes\Model\Attribute;

class Form
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
     * @param \Magento\Customer\Model\Form $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundGetUserAttributes(\Magento\Customer\Model\Form $subject, \Closure $proceed)
    {
        $attributes = $proceed();

        if (in_array($subject->getFormCode(), ['customer_account_edit', 'customer_account_create'])) {
            $amastyAttributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
                'customer',
                Attribute::AMASTY_ATTRIBUTE_CODE
            );

            /* remove our attributes from magento customer attributes form */
            foreach ($amastyAttributes as $attribute) {
                if (array_key_exists($attribute->getAttributeCode(), $attributes)) {
                    unset($attributes[$attribute->getAttributeCode()]);
                }
            }
        }

        return $attributes;
    }
}
