<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$entityType = 'catalog_product';
$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
$path  =  $directory->getRoot().'/pub/scripts/';

/** @var \Retailplace\MiraklConnector\Rewrite\Helper\Offer\Catalog $helper */
$helper = $objectManager->get('Retailplace\MiraklConnector\Rewrite\Helper\Offer\Catalog');
/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();

$sql = "SELECT distinct mo.product_sku FROM `mirakl_offer` as mo WHERE mo.shop_id in (SELECT `id` FROM `mirakl_shop`)";
$skus = $connection->fetchCol($sql);
$helper->updateAttributes($skus);
