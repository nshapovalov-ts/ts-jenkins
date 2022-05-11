<?php

use Mirakl\Mcm\Helper\Data as McmHelper;

/** @var \Magento\Catalog\Model\Product $product */
$product = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId(11)
    ->setName('Test Product')
    ->setSku('mirakl_product_sku')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['qty' => 100, 'is_in_stock' => 1])
    ->setDescription('description')
    ->setShortDescription('short desc')
    ->setTaxClassId(0)
    ->save();

$product->setData(McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID, 'abc5-4cf1-acdb-56152a77bc56');
$product->setBrand('Lacoste');