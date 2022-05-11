<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Model;

use Amasty\CustomerAttributes\Model\Customer\GuestAttributes;
use Amasty\CustomerAttributes\Model\Customer\GuestAttributesFactory;
use Amasty\CustomerAttributes\Model\ResourceModel\Customer\GuestAttributes as GuestAttributesResource;

class CustomerAttributesProcessor
{
    /**
     * @var GuestAttributesFactory
     */
    private $attributeFactory;

    /**
     * @var GuestAttributesResource
     */
    private $attributeResource;

    public function __construct(
        GuestAttributesFactory $attributeFactory,
        GuestAttributesResource $attributeResource
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->attributeResource = $attributeResource;
    }

    /**
     * @param array $customAttributes
     */
    private function prepareAttributes(array &$customAttributes)
    {
        foreach ($customAttributes as &$attribute) {
            if (is_array($attribute)) {
                $attribute = implode(',', $attribute);
            }
        }
    }

    /**
     * @param int $orderId
     * @param array $customAttributes
     */
    public function saveCustomerAttributesGuest($orderId, $customAttributes)
    {
        $this->prepareAttributes($customAttributes);
        /** @var GuestAttributes $attributeModel */
        $attributeModel = $this->attributeFactory->create();
        $attributeModel->setData($customAttributes);
        $attributeModel->setOrderId($orderId);
        $this->attributeResource->save($attributeModel);
    }
}
