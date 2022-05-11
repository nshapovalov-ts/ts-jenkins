<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Webapi;

class ServiceInputProcessor
{
    /**
     * @param \Magento\Framework\Webapi\ServiceInputProcessor $subject
     * @param mixed $data
     * @param string $type Convert given value to the this type
     *
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeConvertValue($subject, $data, $type)
    {
        if (!is_array($data)) {
            return null;
        }
        $attributeType = ['custom_attributes', 'customAttributes'];
        /* fix fatal error with array value from multiselect attributes*/
        foreach ($attributeType as $name) {
            if (array_key_exists($name, $data) && is_array($data[$name])) {
                foreach ($data[$name] as $key => $attributeValue) {
                    if (is_array($attributeValue)) {
                        foreach ($attributeValue as $valueKey => $item) {
                            if ($valueKey === 'attribute_code' || $valueKey === 'value') {
                                continue 2;
                            }
                            if (is_array($item)) {
                                $attributeValue[$valueKey] = implode(',', $item);
                            }
                        }
                        $data[$name][$key] = implode(',', $attributeValue);
                    }
                }
            }
        }

        return [$data, $type];
    }
}
