<?php

use Mirakl\MMP\FrontOperator\Domain\Collection\Shop\ShopCollection;

$shops = json_decode(file_get_contents(__DIR__ . '/shops.json'), true);
$shops = new ShopCollection($shops['shops']);

$shopModel = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Mirakl\Core\Model\ResourceModel\Shop::class);
$processModel = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Mirakl\Process\Model\Process::class);

$shopModel->synchronize($shops, $processModel);
