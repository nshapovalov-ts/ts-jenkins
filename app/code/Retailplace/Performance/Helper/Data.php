<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Helper;

use Exception;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Catalog\Model\View\Asset\ImageFactory as AssertImageFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem;
use Magento\Framework\Image;
use Magento\Framework\Image\Factory as ImageFactory;
use Magento\Framework\View\ConfigInterface as ViewConfig;
use Magento\Store\Model\Store;
use Magento\Theme\Model\Config\Customization as ThemeCustomizationConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Retailplace\Performance\Model\Image as ProductImage;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Mirakl\Mci\Helper\Data as MciHelper;
use Retailplace\Performance\Logger\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var ResourceConnection
     */
    protected $resource;
    /**
     * @var Logger
     */
    protected $_logger;
    /**
     * @var State
     */
    private $appState;
    /**
     * @var MediaConfig
     */
    private $imageConfig;
    /**
     * @var ProductImage
     */
    private $productImage;
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    /**
     * @var ParamsBuilder
     */
    private $paramsBuilder;
    /**
     * @var ViewConfig
     */
    private $viewConfig;
    /**
     * @var AssertImageFactory
     */
    private $assertImageFactory;
    /**
     * @var ThemeCustomizationConfig
     */
    private $themeCustomizationConfig;
    /**
     * @var Collection
     */
    private $themeCollection;
    /**
     * @var Filesystem
     */
    private $mediaDirectory;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Mirakl\Mci\Helper\Data
     */
    private $mciHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;

    /**
     * Data constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Catalog\Model\Product\Media\ConfigInterface $imageConfig
     * @param \Retailplace\Performance\Model\Image $productImage
     * @param \Magento\Framework\Image\Factory $imageFactory
     * @param \Magento\Catalog\Model\Product\Image\ParamsBuilder $paramsBuilder
     * @param \Magento\Framework\View\ConfigInterface $viewConfig
     * @param \Magento\Catalog\Model\View\Asset\ImageFactory $assertImageFactory
     * @param \Magento\Theme\Model\Config\Customization $themeCustomizationConfig
     * @param \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Retailplace\Performance\Logger\Logger $logger
     * @throws \Magento\Framework\Exception\FileSystemException
     * @internal param ProductImage $gallery
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        ResourceConnection $resource,
        State $appState,
        MediaConfig $imageConfig,
        ProductImage $productImage,
        ImageFactory $imageFactory,
        ParamsBuilder $paramsBuilder,
        ViewConfig $viewConfig,
        AssertImageFactory $assertImageFactory,
        ThemeCustomizationConfig $themeCustomizationConfig,
        Collection $themeCollection,
        Filesystem $filesystem,
        EavConfig $eavConfig,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        MciHelper $mciHelper,
        ProductResource $productResource,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->resource = $resource;
        $this->appState = $appState;
        $this->imageConfig = $imageConfig;
        $this->productImage = $productImage;
        $this->imageFactory = $imageFactory;
        $this->paramsBuilder = $paramsBuilder;
        $this->viewConfig = $viewConfig;
        $this->assertImageFactory = $assertImageFactory;
        $this->themeCustomizationConfig = $themeCustomizationConfig;
        $this->themeCollection = $themeCollection;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->filesystem = $filesystem;
        $this->eavConfig = $eavConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->mciHelper = $mciHelper;
        $this->productResource = $productResource;
        $this->_logger = $logger;
    }

    /**
     * Create resized images of different sizes from themes.
     *
     * @param array|null $themes
     * @return \Generator
     * @throws NotFoundException
     */
    public function resizeFromThemes(array $themes = null): \Generator
    {
        error_reporting(E_ALL ^ E_WARNING);

        $count = $this->productImage->getCountAllProductImages();
        if (!$count) {
            throw new NotFoundException(__('Cannot resize images - product images not found'));
        }
        $connection = $this->resource->getConnection();

        $productImages = $this->productImage->getAllProductImages();
        $viewImages = $this->getViewImages($themes ?? $this->getThemesInUse());

        foreach ($productImages as $image) {
            $originalImageName = $image['filepath'];
            $originalImagePath = $this->mediaDirectory->getAbsolutePath(
                $this->imageConfig->getMediaPath($originalImageName)
            );

            foreach ($viewImages as $viewImage) {
                $this->resize($viewImage, $originalImagePath, $originalImageName);
            }

            $connection->update($this->resource->getTableName(Gallery::GALLERY_TABLE), ["is_cached" => 1], ['value_id = ?' => $image['value_id']]);
            yield $originalImageName => $count;
        }
    }

    /**
     * Get view images data from themes.
     *
     * @param array $themes
     * @return array
     */
    private function getViewImages(array $themes): array
    {
        $viewImages = [];
        /** @var \Magento\Theme\Model\Theme $theme */
        foreach ($themes as $theme) {
            $config = $this->viewConfig->getViewConfig([
                'area'       => Area::AREA_FRONTEND,
                'themeModel' => $theme,
            ]);
            $images = $config->getMediaEntities('Magento_Catalog', ImageHelper::MEDIA_TYPE_CONFIG_NODE);
            foreach ($images as $imageId => $imageData) {
                $uniqIndex = $this->getUniqueImageIndex($imageData);
                $imageData['id'] = $imageId;
                $viewImages[$uniqIndex] = $imageData;
            }
        }
        return $viewImages;
    }

    /**
     * Get unique image index.
     *
     * @param array $imageData
     * @return string
     */
    private function getUniqueImageIndex(array $imageData): string
    {
        ksort($imageData);
        unset($imageData['type']);
        return md5(json_encode($imageData));
    }

    /**
     * Search the current theme.
     *
     * @return array
     */
    private function getThemesInUse(): array
    {
        $themesInUse = [];
        $registeredThemes = $this->themeCollection->loadRegisteredThemes();
        $storesByThemes = $this->themeCustomizationConfig->getStoresByThemes();
        $keyType = is_integer(key($storesByThemes)) ? 'getId' : 'getCode';
        foreach ($registeredThemes as $registeredTheme) {
            if (array_key_exists($registeredTheme->$keyType(), $storesByThemes)) {
                $themesInUse[] = $registeredTheme;
            }
        }
        return $themesInUse;
    }

    /**
     * Resize image.
     *
     * @param array $viewImage
     * @param string $originalImagePath
     * @param string $originalImageName
     */
    private function resize(array $viewImage, string $originalImagePath, string $originalImageName)
    {
        try {
            $imageParams = $this->paramsBuilder->build($viewImage);
            $image = $this->makeImage($originalImagePath, $imageParams);
            $imageAsset = $this->assertImageFactory->create(
                [
                    'miscParams' => $imageParams,
                    'filePath'   => $originalImageName,
                ]
            );

            if (isset($imageParams['watermark_file'])) {
                if ($imageParams['watermark_height'] !== null) {
                    $image->setWatermarkHeight($imageParams['watermark_height']);
                }

                if ($imageParams['watermark_width'] !== null) {
                    $image->setWatermarkWidth($imageParams['watermark_width']);
                }

                if ($imageParams['watermark_position'] !== null) {
                    $image->setWatermarkPosition($imageParams['watermark_position']);
                }

                if ($imageParams['watermark_image_opacity'] !== null) {
                    $image->setWatermarkImageOpacity($imageParams['watermark_image_opacity']);
                }

                $image->watermark($this->getWatermarkFilePath($imageParams['watermark_file']));
            }

            if ($imageParams['image_width'] !== null && $imageParams['image_height'] !== null) {
                $image->resize($imageParams['image_width'], $imageParams['image_height']);
            }
            $image->save($imageAsset->getPath());
        } catch (Exception $e) {
            $this->_logger->info("Exception  : " . $e->getMessage());
        }
    }

    /**
     * Make image.
     *
     * @param string $originalImagePath
     * @param array $imageParams
     * @return Image
     */
    private function makeImage(string $originalImagePath, array $imageParams): Image
    {
        $image = $this->imageFactory->create($originalImagePath);
        $image->keepAspectRatio($imageParams['keep_aspect_ratio']);
        $image->keepFrame($imageParams['keep_frame']);
        $image->keepTransparency($imageParams['keep_transparency']);
        $image->constrainOnly($imageParams['constrain_only']);
        $image->backgroundColor($imageParams['background']);
        $image->quality($imageParams['quality']);
        return $image;
    }

    /**
     * Returns watermark file absolute path
     *
     * @param string $file
     * @return string
     */
    private function getWatermarkFilePath($file)
    {
        $path = $this->imageConfig->getMediaPath('/watermark/' . $file);
        return $this->mediaDirectory->getAbsolutePath($path);
    }

    /**
     * Resize product images
     *
     * @param OutputInterface|null $output
     * @param int $limit
     */
    public function resizeProductImages(OutputInterface $output = null, $limit = 100)
    {
        try {
            $connection = $this->resource->getConnection();
            $tableName = $connection->getTableName("catalog_product_entity_int");
            $imageAttributeSelect  = $this->eavConfig->getAttribute(
                Product::ENTITY,
                'is_image_imported'
            );
            $isImageImportAttributeId = $imageAttributeSelect->getId();

            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter('is_image_imported', ['eq' => 1]);
            if ($limit) {
                $productCollection->getSelect()->limit($limit);
            }
            $productCollection->addMediaGalleryData();
            $productCollection->setStoreId(0);

            $productIds = [];
            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($productCollection as $product) {
                try {
                    $start = microtime(true);
                    $images = $product->getMediaGalleryImages();
                    $isResized = false;
                    if (!$images->getSize()) {
                        $this->markImagesToBeProcessed($product);
                        $this->log($output, "Product Id : {$product->getId()} ---- Product Sku :  {$product->getSku()}");
                        $this->log($output, "Exception  : No image found for this product");

                    }
                    foreach ($images as $image) {
                        try {
                            $this->resizeFromImageName($image->getData('file'));
                            $isResized = true;
                        } catch (Exception $resizeException) {
                            try {
                                $this->markImagesToBeProcessed($product);
                            } catch (Exception $markProcessException) {
                                $this->log($output, $markProcessException->getMessage());
                            }
                            $this->log($output, "Product Id : {$product->getId()} ---- Product Sku :  {$product->getSku()}");
                            $this->log($output, "Exception   : " . $resizeException->getMessage() . " Image : " . $image->getData('file'));
                        }
                    }
                    if ($isResized) {
                        $productIds[] = $product->getId();
                        $data = ["attribute_id" => $isImageImportAttributeId, "store_id" => 0, "entity_id" => $product->getId(), "value" => 0];
                        $connection->insertOnDuplicate($tableName, $data);
                    }

                    $time = round(microtime(true) - $start, 2);
                    $this->log($output, "Product Id : {$product->getId()} ---- Product Sku :  {$product->getSku()} processed in $time" . "s");
                } catch (Exception $productException) {
                    $this->log(
                        $output,
                        "Product Id : {$product->getId()} ---- Product Sku :  {$product->getSku()} something went wrong due this exception :\n {$productException->getMessage()}"
                    );
                }
            }
        } catch (Exception $e) {
            $this->log($output, "Something went wrong {$e->getMessage()}");
        }
    }

    /**
     * Create resized images of different sizes from an original image.
     *
     * @param string $originalImageName
     * @throws NotFoundException
     */
    public function resizeFromImageName(string $originalImageName)
    {
        $originalImagePath = $this->mediaDirectory->getAbsolutePath(
            $this->imageConfig->getMediaPath($originalImageName)
        );
        if (!$this->mediaDirectory->isFile($originalImagePath)) {
            throw new NotFoundException(__('Cannot resize image "%1" - original image not found', $originalImagePath));
        }
        foreach ($this->getViewImages($this->getThemesInUse()) as $viewImage) {
            $this->resize($viewImage, $originalImagePath, $originalImageName);
        }
    }

    /**
     * Log message
     *
     * @param $output
     * @param string $message
     */
    private function log($output, $message)
    {
        if ($output) {
            $output->writeln($message);
        }
        $this->_logger->info($message);
    }

    /**
     * Mark product that images should be uploaded
     *
     * @param \Magento\Catalog\Model\Product $product
     * @throws \Exception
     */
    private function markImagesToBeProcessed(Product $product)
    {
        $attributes = $this->productImage->getNonProcessedImageAttributes();
        foreach ($attributes as $attribute) {
            $product->setData($attribute->getAttributeCode(), 1);
        }
        $product->setStoreId(Store::DEFAULT_STORE_ID);

        $this->productResource->save($product);
    }
}
