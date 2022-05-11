<?php

use Magento\Catalog\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$variantAttributes = ['color', 'size'];

foreach ($variantAttributes as $attrCode) {
    /** @var EavAttribute $attribute */
    $attribute = $objectManager->create(EavAttribute::class)->loadByCode('catalog_product', $attrCode);
    $attribute->setData('mirakl_is_variant', 1);
    try {
        $objectManager->create(AttributeResource::class)->save($attribute);
    } catch(\Exception $e) {}
}