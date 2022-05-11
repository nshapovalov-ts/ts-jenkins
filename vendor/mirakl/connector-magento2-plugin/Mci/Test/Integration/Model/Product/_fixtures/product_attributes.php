<?php

use Magento\Catalog\Model\Product;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
$gearAttributeSetId = 11;
$attributeSet = $objectManager->create(\Magento\Eav\Model\Entity\Attribute\Set::class)->load($gearAttributeSetId);

/** @var \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->create(\Magento\Eav\Api\AttributeRepositoryInterface::class);

/** @var \Magento\Catalog\Model\Entity\Attribute $attribute */
// Add attribute size to Gear attribute set
$attribute = $attributeRepository->get('catalog_product', 'size');

$attributeManagement = $objectManager->create(\Magento\Eav\Model\AttributeManagement::class);
$attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'size', 100);

/** @var \Magento\Eav\Model\Entity\Type $entityType */
$entityType = $objectManager->create(\Magento\Eav\Model\Entity\Type::class)->loadByCode('catalog_product');

try {
    $attribute = $attributeRepository->get('catalog_product', 'brand');
    $attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'brand', 110);
} catch (\Exception $e) {
    // Ignore exception and add attribute
    $attributeData = [
        'entity_type_id'     => $entityType->getId(),
        'attribute_code'     => 'brand',
        'frontend_input'     => 'text',
        'frontend_label'     => 'Brand',
        'backend_type'       => 'varchar',
        'is_required'        => 1,
        'is_user_defined'    => 1,
        'attribute_set_id'   => $attributeSet->getId(),
        'attribute_group_id' => $attributeSet->getDefaultGroupId(),
    ];

    $attribute = $objectManager->create(\Magento\Catalog\Model\Entity\Attribute::class);
    $attribute->setData($attributeData);
    $attributeRepository->save($attribute);

    $attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'brand', 110);
}

try {
    $attribute = $attributeRepository->get('catalog_product', 'mirakl_image_1');
    $attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'mirakl_image_1', 110);
} catch (\Exception $e) {
    // Ignore exception and add attribute
    $attributeData = [
        'entity_type_id'     => $entityType->getId(),
        'attribute_code'     => 'mirakl_image_1',
        'frontend_input'     => 'text',
        'frontend_label'     => 'Mirakl Image #1',
        'backend_type'       => 'varchar',
        'is_required'        => 1,
        'is_user_defined'    => 1,
        'attribute_set_id'   => $attributeSet->getId(),
        'attribute_group_id' => $attributeSet->getDefaultGroupId(),
    ];

    /** @var \Magento\Catalog\Model\Entity\Attribute $attribute */
    $attribute = $objectManager->create(\Magento\Catalog\Model\Entity\Attribute::class);
    $attribute->setData($attributeData);
    $attributeRepository->save($attribute);

    $attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'mirakl_image_1', 120);
}

try {
    $attribute = $attributeRepository->get('catalog_product', 'ean');
    $attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'ean', 110);
} catch (\Exception $e) {
    // Ignore exception and add attribute
    $attributeData = [
        'entity_type_id'     => $entityType->getId(),
        'attribute_code'     => 'ean',
        'frontend_input'     => 'text',
        'frontend_label'     => 'EAN',
        'backend_type'       => 'varchar',
        'is_required'        => 1,
        'is_user_defined'    => 1,
        'attribute_set_id'   => $attributeSet->getId(),
        'attribute_group_id' => $attributeSet->getDefaultGroupId(),
    ];

    /** @var \Magento\Catalog\Model\Entity\Attribute $attribute */
    $attribute = $objectManager->create(\Magento\Catalog\Model\Entity\Attribute::class);
    $attribute->setData($attributeData);
    $attributeRepository->save($attribute);

    $attributeManagement->assign(Product::ENTITY, $attributeSet->getId(), $attributeSet->getDefaultGroupId(), 'ean', 120);
}
