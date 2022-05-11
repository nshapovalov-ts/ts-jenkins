<?php

/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
$productCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);

/** @var \Magento\Catalog\Model\Product $product */
$product = $productCollection
    ->addFieldToFilter('sku', 'SHOPSKU')
    ->getFirstItem();

$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId(11)
    ->setName('Test Product')
    ->setSku('SHOPSKU')
    ->setPrice(10)
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setWebsiteIds([1])
    ->setStockData(['qty' => 100, 'is_in_stock' => 1])
    ->setDescription('description')
    ->setShortDescription('short desc')
    ->setTaxClassId(0)
    ->save();
