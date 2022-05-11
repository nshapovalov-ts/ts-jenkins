<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\View\Element\UIComponent\DataProvider;

class Document
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    private $uiFilter;

    /**
     * @var \Amasty\CustomerAttributes\Model\Attribute
     */
    private $attribute;

    /**
     * Document constructor.
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Ui\Component\MassAction\Filter $uiFilter
     */
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Ui\Component\MassAction\Filter $uiFilter,
        \Amasty\CustomerAttributes\Model\Attribute $attribute
    ) {
        $this->eavConfig = $eavConfig;
        $this->uiFilter = $uiFilter;
        $this->attribute = $attribute;
    }

    /**
     * @param \Magento\Customer\Ui\Component\DataProvider\Document $subject
     * @param \Magento\Framework\Api\AttributeValue $result
     * @return \Magento\Framework\Api\AttributeValue
     */
    public function afterGetCustomAttribute(
        $subject,
        $result
    ) {
        $component = $this->uiFilter->getComponent();
        if ($component->getName() == 'customer_listing' || $component->getName() == 'sales_order_grid') {
            $attributeCode = $result->getAttributeCode();
            if ($this->attribute->isOurAttribute($attributeCode)) {
                /** @var \Magento\Customer\Model\Attribute $attribute */
                $attribute = $this->eavConfig->getAttribute('customer', $attributeCode);
                $result = $this->prepareOutput($attribute, $result);
            }
        }

       return $result;
    }

    /**
     * @param \Magento\Customer\Model\Attribute $attribute
     * @param \Magento\Framework\Api\AttributeValue $result
     * @return \Magento\Framework\Api\AttributeValue
     */
    private function prepareOutput($attribute, $result)
    {
        switch ($attribute->getFrontendInput()) {
            case 'boolean':
                if ($result->getValue()) {
                    $value = __('Yes');
                } else {
                    $value = __('No');
                }
                $result->setValue($value);
                break;
            case 'select':
            case 'selectimg':
            case 'multiselectimg':
            case 'multiselect':
                $arrayValue = $attribute->getSource()->getOptionText($result->getValue());
                if (is_array($arrayValue)) {
                    $result->setValue(implode(',', $arrayValue));
                } else {
                    $result->setValue($arrayValue);
                }
                break;
        }

        return $result;
    }
}
