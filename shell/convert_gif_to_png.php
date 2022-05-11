<?php
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$filesystem = $objectManager->get(Filesystem::class);
$mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
$imageConfig = $objectManager->get(MediaConfig::class);
$productGallery = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Gallery');
$imageProcessor = $objectManager->get(\Magento\Catalog\Model\Product\Gallery\Processor::class);
$connection = $resource->getConnection();
$select = $connection->select()
    ->from(
        [
            'cpemg' => $resource->getTableName('catalog_product_entity_media_gallery')
        ],
        ['value_id','value']
    )->joinLeft(
        [
            'cpemgv' => $resource->getTableName('catalog_product_entity_media_gallery_value')
        ],
        "cpemgv.value_id = cpemg.value_id",
        ['entity_id']
    )
    ->where("cpemg.value like ?", "%.gif%");
$allGifImages = $connection->fetchAll($select);

foreach ($allGifImages as $entries) {
    $valueId = $entries['value_id'];
    $productId = $entries['entity_id'];
    try {
        $originalImagePath = $mediaDirectory->getAbsolutePath(
            $imageConfig->getMediaPath($entries['value'])
        );
        if (file_exists($originalImagePath) && is_file($originalImagePath)) {
            $product = $objectManager->create('\Magento\Catalog\Model\Product');
            $product->load($productId);

            $command = '/usr/bin/convert';
            $target = str_replace(".gif", ".png", $originalImagePath);
            $exec = $command . " " . escapeshellarg($originalImagePath) . " -coalesce " . escapeshellarg($target);
            $out = exec($exec);
            $path_parts = pathinfo($originalImagePath);
            $directory = $path_parts['dirname'];
            $filename = $path_parts['filename'];
            $dbDirName = dirname($entries['value']);

            chdir($directory);
            foreach (glob($filename . "*.png") as $image_file) {
                $imageFile = $imageConfig->getMediaPath("$dbDirName/$image_file");
                $mediaAttribute = null;
                if ($product->getImage() == $entries['value']) {
                    $mediaAttribute = ['image', 'small_image', 'thumbnail'];
                }
                $product->addImageToMediaGallery($imageFile, $mediaAttribute, false, false);
                echo "Added image {$imageFile} to product {$productId}\n";
                break;
            }
            $product->save();
            $productGallery->deleteGallery($valueId);
            $imageProcessor->removeImage($product, $imageConfig->getMediaPath($entries['value']));
            echo "Deleted image {$originalImagePath} from product {$productId}\n";
        }
    } catch (\Exception $e) {
        echo "Something went wrong {$productId}\n";
        echo "Error : " . $e->getMessage() . "\n";
    }
}
