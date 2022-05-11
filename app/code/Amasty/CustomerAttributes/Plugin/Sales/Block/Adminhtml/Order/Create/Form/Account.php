<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Sales\Block\Adminhtml\Order\Create\Form;

use Amasty\CustomerAttributes\Model\Attribute;
use Amasty\CustomerAttributes\Model\Customer\GuestAttributes;
use Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory;
use Magento\Backend\Model\Session\Quote;
use Magento\Customer\Model\AttributeMetadataDataProvider;

class Account
{
    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * Session quote
     *
     * @var Quote
     */
    private $sessionQuote;

    /**
     * @var GuestAttributesFactory
     */
    private $guestAttributesFactory;

    public function __construct(
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        Quote $sessionQuote,
        GuestAttributesFactory $guestAttributesFactory
    ) {
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->sessionQuote = $sessionQuote;
        $this->guestAttributesFactory = $guestAttributesFactory;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Form\Account $subject
     * @param array $result
     * @return array
     */
    public function afterGetFormValues(\Magento\Sales\Block\Adminhtml\Order\Create\Form\Account $subject, $result)
    {
        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            Attribute::AMASTY_ATTRIBUTE_CODE
        );
        $origOrderId = $this->sessionQuote->getOrderId();

        if (empty($origOrderId)) {
            $origOrderId = $this->sessionQuote->getReordered();
        }

        /** @var GuestAttributes $model */
        $model = $this->guestAttributesFactory->create()
            ->loadByOrderId($origOrderId);

        if (!empty($result['is_guest']) && $model && $model->getId()) {
            $attributesData = $model->getData();
        } else {
            $attributesData = $result;
        }

        foreach ($attributes->getItems() as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $attributeValue = $attributesData[$attributeCode] ?? $attribute->getDefaultValue();

            if ($attributeValue !== null) {
                $result[$attributeCode] = $attributeValue;
            }
        }

        return $result;
    }
}
