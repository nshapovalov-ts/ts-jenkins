<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Backend\Block\Widget\Grid\Column\Renderer;

use Amasty\CustomerAttributes\Model\Attribute;
use \Magento\Framework\DataObject;

class AbstractRenderer
{
    /**
     * @var bool
     */
    private $isCustomerGrid;

    /**
     * @var \Magento\Customer\Model\AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * AbstractRenderer constructor.
     * @param \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        \Magento\Customer\Model\AttributeMetadataDataProvider $attributeMetadataDataProvider,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer $subject
     * @param DataObject $row
     */
    public function beforeRender(
        $subject,
        DataObject $row
    ) {
        $this->isCustomerGrid = false;
        if ($row instanceof \Magento\Customer\Model\Backend\Customer) {
            $this->isCustomerGrid = true;
        }
    }

    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer $subject
     * @param string $result
     * @return mixed
     */
    public function afterRender(
        $subject,
        $result
    ) {
        if ($this->isCustomerGrid) {
            $column = $subject->getColumn();
            $attributeId = $column->getId();
            if ($this->isOurAttribute($attributeId)) {
                $attributeCode = $column->getIndex();
                /** @var \Magento\Customer\Model\Attribute $attribute */
                $attribute = $this->eavConfig->getAttribute('customer', $attributeCode);
                $result = $this->prepareOutput($attribute, $result);
            }
        }
        return $result;
    }

    /**
     * @param int $attributeId
     * @return bool
     */
    private function isOurAttribute($attributeId)
    {
        $isOurAttribute = false;

        $ourAttributeIds = $this->attributeMetadataDataProvider
            ->loadAttributesCollection(
                'customer',
                Attribute::AMASTY_ATTRIBUTE_CODE
            )->getColumnValues('attribute_id');

        if (in_array($attributeId, $ourAttributeIds)) {
            $isOurAttribute = true;
        }

        return $isOurAttribute;
    }

    /**
     * @param \Magento\Customer\Model\Attribute $attribute
     * @param mixed $result
     * @return string|null
     */
    private function prepareOutput($attribute, $result)
    {
        switch ($attribute->getFrontendInput()) {
            case 'boolean':
                if ($result) {
                    $result = __('Yes');
                } else {
                    $result = __('No');
                }
                break;
            case 'select':
                $result = $attribute->getSource()->getOptionText($result);
                break;
        }

        return $result;
    }
}
