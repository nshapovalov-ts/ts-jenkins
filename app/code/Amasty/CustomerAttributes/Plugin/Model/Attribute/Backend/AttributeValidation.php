<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Model\Attribute\Backend;

use Amasty\CustomerAttributes\Block\Customer\Form\Attributes as AttributesForm;
use Amasty\CustomerAttributes\Model\Attribute as AttributeModel;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Changing the visibility of child attributes before validation
 */
class AttributeValidation
{
    /**
     * Restriction of the depth of relation check for processing dependent elements
     */
    const RELATION_CHECK_DEPTH_LIMIT = 50;

    /**
     * @var AttributesForm
     */
    private $formAttributes;

    /**
     * @var AttributeModel
     */
    private $attributeModel;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * AttributeValidation constructor.
     * @param AttributesForm $formAttributes
     */
    public function __construct(
        AttributesForm $formAttributes,
        AttributeModel $attributeModel,
        ArrayManager $arrayManager
    ) {
        $this->formAttributes = $formAttributes;
        $this->attributeModel = $attributeModel;
        $this->arrayManager = $arrayManager;
    }

    /**
     * Hide our attributes according to configurations
     *
     * @param \Magento\Eav\Model\Entity\Attribute\Backend\Increment $subject
     * @param \Magento\Customer\Model\Customer $object
     */
    public function beforeValidate(
        $subject,
        $object
    ) {
        /** @var \Magento\Customer\Model\Attribute $attribute */
        $attribute = $subject->getAttribute();

        if (!$this->formAttributes->isAttributeVisible($attribute)
            || !$this->isAttributeVisible($attribute, $object)
        ) {
            $attribute->setIsVisible(false);
        }
    }

    /**
     * Check whether attribute is visible
     *
     * @param Attribute $attribute
     * @param $object
     * @return bool
     * @throws LocalizedException
     */
    protected function isAttributeVisible(Attribute $attribute, $object)
    {
        $ourAttributes = $this->attributeModel->extractCustomerAttributeCodes($object);
        $attributeDetails = $this->attributeModel->getAttributeDetails($object);

        return $this->isAttributeVisibleRecursion($attribute, $ourAttributes, $attributeDetails, 0);
    }

    /**
     * Recursive check, up the relation tree, if attribute is visible
     *
     * @param Attribute $attribute
     * @param array &$ourAttributes
     * @param array &$attributeDetails
     * @param int $deep
     * @return bool
     * @throws LocalizedException
     */
    protected function isAttributeVisibleRecursion(
        Attribute $attribute,
        array &$ourAttributes,
        array &$attributeDetails,
        $deep = 0
    ) {
        $depthLimit = $this->getRelationCheckDepthLimit();

        if ($deep > $depthLimit) {
            throw new LocalizedException(__('Relation check depth limit reached'));
        }

        if (!$this->attributeModel->isOurAttribute($attribute->getAttributeCode())
            || empty($attributeDetails)
        ) {
            return true;
        }

        $attributeId = $attribute->getId();
        $optionId = $this->arrayManager->get($attributeId . '/option_id', $attributeDetails);

        if (!empty($optionId) && ($optionId != $attribute->getDefaultValue())) {
            return false;
        }

        $parentAttributesIds = $this->arrayManager->get($attributeId . '/parent_attributes', $attributeDetails) ?? [];
        $deep++;

        foreach ($parentAttributesIds as $parentAttributesId) {
            $parentAttribute = $this->arrayManager->get($parentAttributesId, $ourAttributes);

            if (empty($parentAttribute)) {
                throw new LocalizedException(__('Invalid parent attribute'));
            }

            if (!$this->isAttributeVisibleRecursion($parentAttribute, $ourAttributes, $attributeDetails, $deep)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get restriction of the depth of relation check
     *
     * @return int
     */
    protected function getRelationCheckDepthLimit()
    {
        return self::RELATION_CHECK_DEPTH_LIMIT;
    }
}
