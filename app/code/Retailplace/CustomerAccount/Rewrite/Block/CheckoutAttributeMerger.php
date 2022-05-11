<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

namespace Retailplace\CustomerAccount\Rewrite\Block;

use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\CustomerAccount\Block\Widget\BusinessName;

/**
 * Class CheckoutAttributeMerger
 */
class CheckoutAttributeMerger extends AttributeMerger
{
    /**
     * Return default value for Company Attribute.
     *
     * @param string $attributeCode
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @return null|string
     */
    protected function getDefaultValue($attributeCode): ?string
    {
        $attributeValue = parent::getDefaultValue($attributeCode);
        if ($attributeCode == 'company') {
            $customer = $this->getCustomer();
            if ($customer) {
                $attribute = $customer->getCustomAttribute(BusinessName::ATTRIBUTE_CODE);
                if ($attribute) {
                    $attributeValue = $attribute->getValue();
                }
            }
        }

        return $attributeValue;
    }
}
