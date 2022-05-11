<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Email\Model;

use Amasty\CustomerAttributes\Model\Attribute;
use Magento\Customer\Model\AttributeMetadataDataProvider;

class Template
{
    const IS_SALES_EMAIL_VARIABLE = '{{var order.increment_id}}';

    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    public function __construct(AttributeMetadataDataProvider $attributeMetadataDataProvider)
    {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
    }

    /**
     * Plugin for adding attributes to marketing order emails to Insert Variable.
     *
     * @param \Magento\Framework\Mail\TemplateInterface|\Magento\Email\Model\Template $subject
     * @param array $result
     *
     * @return array $result
     */
    public function afterGetVariablesOptionArray($subject, $result)
    {
        if (!empty($result['value']) && $this->isSalesEmail($result)) {
            $attributesMetaCollection = $this->attributeMetadataDataProvider
                ->loadAttributesCollection(
                    'customer',
                    Attribute::AMASTY_ATTRIBUTE_CODE
                );
            foreach ($attributesMetaCollection as $attribute) {
                /** @var $attribute \Magento\Customer\Model\Attribute */
                $result['value'][] = [
                    'label' => 'Amasty Customer Attribute: ' . $attribute->getFrontendLabel(),
                    'value' => '{{var order.getCustomer().getData(\'' . $attribute->getAttributeCode() . '\')}}'
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    private function isSalesEmail($result)
    {
        foreach ($result['value'] as $variable) {
            if ($variable['value'] === self::IS_SALES_EMAIL_VARIABLE) {

                return true;
            }
        }

        return false;
    }
}
