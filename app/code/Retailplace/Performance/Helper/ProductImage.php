<?php

/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Helper;

use Kipanga\Webpimg\Helper\Mirakl\Mci\Product\Image;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Store\Model\Store;
use Mirakl\Process\Model\Process;
use Retailplace\Performance\Model\Image as ProductImageModel;
use Magento\Catalog\Model\Product\Gallery\Processor as MediaGalleryProcessor;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\ConfigurableProduct\Model\Product\ReadHandler;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Mirakl\Api\Helper\Config as ApiConfigHelper;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Model\Image\Downloader as ImageDownloader;

/**
 * Class ProductImage
 */
class ProductImage extends Image
{

    /** @var \Retailplace\Performance\Model\Image */
    private $productImage;

    /**
     * ProductImage constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mirakl\Api\Helper\Config $apiConfigHelper
     * @param \Mirakl\Core\Helper\Data $coreHelper
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     * @param \Mirakl\Mci\Model\Image\Downloader $imageDownloader
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Catalog\Model\Product\Gallery\Processor $mediaGalleryProcessor
     * @param \Magento\ConfigurableProduct\Model\Product\ReadHandler $configurableReadHandler
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productResourceFactory
     * @param \Retailplace\Performance\Model\Image $productImage
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        ApiConfigHelper $apiConfigHelper,
        CoreHelper $coreHelper,
        MciHelper $mciHelper,
        ImageDownloader $imageDownloader,
        ProductCollectionFactory $productCollectionFactory,
        Filesystem $filesystem,
        MediaGalleryProcessor $mediaGalleryProcessor,
        ReadHandler $configurableReadHandler,
        ProductResourceFactory $productResourceFactory,
        ProductImageModel $productImage
    ) {
        $this->productImage = $productImage;

        parent::__construct(
            $context,
            $apiConfigHelper,
            $coreHelper, $mciHelper,
            $imageDownloader,
            $productCollectionFactory,
            $filesystem,
            $mediaGalleryProcessor,
            $configurableReadHandler,
            $productResourceFactory
        );
    }

    /**
     * Synchronizes Mirakl images (mirakl_image_* attributes) :
     *
     * 1. Download images if URL is specified
     * 2. Add images to product
     * 3. Define images as default if no image where present
     *
     * @param Process $process
     * @param int $limit
     * @return  $this
     * @throws  \Exception
     */
    public function run(Process $process, $limit = 100)
    {
        try {
            set_time_limit(0); // Script may take a while

            $this->apiConfigHelper->disable();

            // Retrieve mirakl_image_* attributes
            $attributes = $this->mciHelper->getImagesAttributes();

            if (empty($attributes)) {
                $process->output(__('No image attribute found.'));

                return $this;
            }

            $process->output(__(
                'Found %1 image attribute%2 (%3).',
                count($attributes),
                count($attributes) > 1 ? 's' : '',
                implode(', ', array_keys($attributes))
            ), true);

            $productCollection = $this->getProductCollection($attributes, $limit);

            $collectionCount = $productCollection->getSize();
            $process->output(__(
                'Found %1 product%2 to process.',
                $collectionCount,
                $collectionCount > 1 ? 's' : ''
            ));

            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($productCollection as $product) {
                $start = microtime(true);
                $this->configurableReadHandler->execute($product);

                $process->output(__('Processing images for product %1...', $product->getId()));
                $process->output(__('Product Type ID : ' . $product->getTypeId() . ' || Visibility : ' . $product->getVisibility()));
                $product->setStoreId(0); // force admin area on product

                $images = [];
                $deletedImages = false;
                foreach ($attributes as $attribute) {
                    //===Downloading time recorded started
                    $downloadStart = microtime(true);
                    /** @var EavAttribute $attribute */
                    if (!$url = $product->getData($attribute->getAttributeCode())) {
                        $product->setData("non_processed_" . $attribute->getAttributeCode(), 0);
                        continue;
                    }

                    if ($url == self::DELETED_IMAGE_URL) {
                        $product->setData($attribute->getAttributeCode(), '');
                        $deletedImages = true;
                        continue;
                    }

                    $url = $this->prepareUrl($url);

                    $process->output(__('Downloading image %1', $url));

                    $process->output(__('T--- Downloading processed in %1s', round(microtime(true) - $downloadStart, 2)), true);

                    //===Downloading time recorded ended

                    //===Image saved in server time recorded started
                    $imageSaveToServerStart = microtime(true);
                    $pathParts = pathinfo(basename(parse_url($url, PHP_URL_PATH)));
                    $file = sprintf(
                        '%s%s%s_%s',
                        rtrim($this->mediaDirectory->getAbsolutePath(), DIRECTORY_SEPARATOR),
                        DIRECTORY_SEPARATOR,
                        $pathParts['filename'],
                        uniqid()
                    );

                    if (isset($pathParts['extension'])) {
                        $file .= '.' . $pathParts['extension'];
                    }

                    try {
                        file_put_contents($file, $this->imageDownloader->download($url));

                        $fileSize = filesize($file);
                        if (!$fileSize) {
                            @unlink($file);
                            throw new \ErrorException(__('Image file is empty after download'));
                        }
                    } catch (\Exception $e) {
                        $process->output(__('ERROR: %1', $e->getMessage()));
                        continue; // Try next image
                    }
                    $process->output(__('T--- Image saved in server  processed in %1s', round(microtime(true) - $imageSaveToServerStart, 2)), true);
                    //===Image saved in server time recorded ended
                    //===Image saved in object time recorded started
                    $imageSaveToObjectStart = microtime(true);
                    switch (exif_imagetype($file)) {
                        case IMAGETYPE_GIF:
                            $ext = '.gif';
                            break;
                        case IMAGETYPE_JPEG:
                            $ext = '.jpg';
                            break;
                        case IMAGETYPE_PNG:
                            $ext = '.png';
                            break;
                        case IMAGETYPE_WEBP:
                            $ext = '.webp';
                            break;
                        default:
                            $process->output(__('ERROR: Could not detect image type'));
                            @unlink($file);
                            continue 2; // Stop all images download of current product and try next
                    }

                    if (!isset($pathParts['extension'])) {
                        rename($file, $file . $ext);
                        $file = $file . $ext;
                    }

                    if (is_file($file)) {
                        $process->output(__('OK (%1)', $this->coreHelper->formatSize($fileSize)));
                        foreach ($product->getMediaAttributes() as $imageAttribute) {
                            /** @var EavAttribute $imageAttribute */
                            $imageAttributeCode = $imageAttribute->getAttributeCode();
                            if (!isset($images[$file])) {
                                $images[$file] = [];
                            }
                            $images[$file][] = $imageAttributeCode;
                        }
                    }

                    $url = $this->coreHelper->addQueryParamToUrl($url, 'processed', 'true');
                    $product->setData($attribute->getAttributeCode(), $url);
                    $product->setData('non_processed_' . $attribute->getAttributeCode(), 0);
                    $process->output(__('T--- Image saved in object  processed in %1s', round(microtime(true) - $imageSaveToObjectStart, 2)), true);
                    //===Image saved in object time recorded ended
                }

                if (empty($images) && !$deletedImages) {
                    continue; // No valid image to save, continue to next product
                }
                //===Remove old images time recorded started
                $removeOldImages = microtime(true);
                // Remove old images
                if ($product->getMediaGalleryEntries()) {
                    $process->output(__('Removing old images...'), true);
                    /** @var \Magento\Catalog\Model\Product\Gallery\Entry $entry */
                    foreach ($product->getMediaGalleryEntries() as $entry) {
                        $this->mediaGalleryProcessor->removeImage($product, $entry->getFile());
                    }
                    $process->output(__('Done!'));
                }

                $process->output(__('Saving images for product %1...', $product->getId()));

                $process->output(__('T--- Remove old images  processed in %1s', round(microtime(true) - $removeOldImages, 2)), true);
                //===Remove old images time  recorded ended

                //===Reverse order images time recorded started
                $reverseOrderImageStart = microtime(true);
                // Reverse order because roles (main, thumbnail...) are set on last image
                foreach (array_reverse($images) as $file => $imageAttributeList) {
                    try {
                        $process->output(__('Adding "%1"', basename($file)));
                        $product->addImageToMediaGallery($file, $imageAttributeList, false, false);
                        @unlink($file);
                    } catch (\Exception $e) {
                        $process->output(__('ERROR: %1', $e->getMessage()));
                    }
                }
                $process->output(__('T---  Reverse order images  processed in %1s', round(microtime(true) - $reverseOrderImageStart, 2)), true);
                //===Reverse order images time  recorded ended

                //===Restore real image order images time recorded started
                $restoreRealImageOrderImageStart = microtime(true);

                // Restore real image order
                $attrCode = $this->mediaGalleryProcessor->getAttribute()->getAttributeCode();
                $mediaGalleryData = $product->getData($attrCode);

                $i = count($mediaGalleryData['images']);
                foreach ($mediaGalleryData['images'] as &$image) {
                    $image['position'] = --$i;
                }
                $product->setData($attrCode, $mediaGalleryData);

                $process->output(__('Saving product %1...', $product->getId()));

                $process->output(__('T---  Restore real image order processed in %1s', round(microtime(true) - $restoreRealImageOrderImageStart, 2)), true);
                //===Restore real image order images  time  recorded ended
                try {
                    //===Product saved to DB time recorded started
                    $productsavedStart = microtime(true);
                    $product->setData('is_image_imported', 1);
                    $product->setStoreId(Store::DEFAULT_STORE_ID);
                    $this->productResource->save($product);
                    $process->output(__('T---  Product saved to DB processed in %1s', round(microtime(true) - $productsavedStart, 2)), true);
                    //===Product saved to DB  time  recorded ended
                } catch (\Exception $e) {
                    $process->output(__('ERROR: %1', $e->getMessage()));
                }

                $process->output(__('Done!'), true);

                $time = round(microtime(true) - $start, 2);
                $process->output(__('T---  Images processed in %1s', $time), true);
            }
        } catch (\Exception $e) {
            $process->fail(__('ERROR: %1', $e->getMessage()));
            throw $e;
        }

        return $this;
    }

    /**
     * Get Product Collection
     *
     * @param array $attributes
     * @param int $limit
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProductCollection($attributes, $limit)
    {
        $productCollection = $this->productCollectionFactory->create();
        $nonProcessedImageAttributes = $this->productImage->getNonProcessedImageAttributes();

        $processedConditions = [];
        foreach ($nonProcessedImageAttributes as $attribute) {
            /** @var EavAttribute $attribute */
            $processedConditions[] = [
                'attribute' => $attribute->getAttributeCode(),
                'eq' => 1
            ];
        }

        $imageConditions = [];
        foreach ($attributes as $attribute) {
            /** @var EavAttribute $attribute */
            $imageConditions[] = [
                'attribute' => $attribute->getAttributeCode(),
                'neq' => 'NULL',
            ];
        }

        $productCollection->addAttributeToFilter($processedConditions, null, 'left');
        $productCollection->addAttributeToFilter($imageConditions, null, 'left');
        $simpleCollection = clone $productCollection;
        $simpleCollection->addAttributeToFilter('type_id', 'simple');
        $simpleCollection->addAttributeToFilter('visibility', 4);
        if ($simpleCollection->getSize()) {
            $productCollection = $simpleCollection;
        } else {
            $visibleCollection = clone $productCollection;
            $visibleCollection->addAttributeToFilter('visibility', 4);
            if ($visibleCollection->getSize()) {
                $productCollection = $visibleCollection;
            }
        }
        if ($limit) {
            $productCollection->getSelect()->limit($limit);
        }

        $productCollection->setStoreId(0);
        $productCollection->addAttributeToSelect('*');
        $productCollection->addMediaGalleryData(); // /!\ Loads productCollection implicitly

        return $productCollection;
    }
}

