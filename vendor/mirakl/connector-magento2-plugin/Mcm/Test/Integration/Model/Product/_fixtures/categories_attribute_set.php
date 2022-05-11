<?php

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\CategoryFactory;
use Mirakl\Mci\Helper\Data as MciHelper;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var CategoryFactory $categoryFactory */
$categoryFactory = $objectManager->create(CategoryFactory::class);

/** @var CategoryResource $categoryResource */
$categoryResource = $objectManager->create(CategoryResource::class);

/** @var Category $category */
$category = $categoryFactory->create();
$category->isObjectNew(true);
$category->setId(3)
    ->setName('Category 1')
    ->setParentId(2)
    ->setPath('1/2/3')
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setData(MciHelper::ATTRIBUTE_ATTR_SET, 11) // Associate the Gear attribute set to the category
    ->save();
$categoryResource->save($category);
