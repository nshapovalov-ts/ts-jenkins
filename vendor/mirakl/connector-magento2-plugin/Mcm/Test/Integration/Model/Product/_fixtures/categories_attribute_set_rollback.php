<?php

use Mirakl\Mci\Helper\Data as MciHelper;

/** @var \Magento\Framework\Registry $registry */
/** @var \Magento\Catalog\Model\Category $category */
$category = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Category::class);
$category->load(3);
$category->unsetData(MciHelper::ATTRIBUTE_ATTR_SET);
$category->save();
