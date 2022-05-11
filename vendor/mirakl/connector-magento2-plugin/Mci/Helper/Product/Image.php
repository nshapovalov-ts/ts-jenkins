<?php
namespace Mirakl\Mci\Helper\Product;

use Magento\Catalog\Model\Product\Gallery\Processor as MediaGalleryProcessor;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\ConfigurableProduct\Model\Product\ReadHandler;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriteInterface;
use Mirakl\Api\Helper\Config as ApiConfigHelper;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Model\Image\Downloader as ImageDownloader;
use Mirakl\Process\Model\Process;

class Image extends AbstractHelper
{
    const DELETED_IMAGE_URL = 'http://delete.image?processed=false';

    /**
     * @var ApiConfigHelper
     */
    protected $apiConfigHelper;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @var ImageDownloader
     */
    protected $imageDownloader;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var DirectoryWriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var MediaGalleryProcessor
     */
    protected $mediaGalleryProcessor;

    /**
     * @var ReadHandler
     */
    protected $configurableReadHandler;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @param   Context                     $context
     * @param   ApiConfigHelper             $apiConfigHelper
     * @param   CoreHelper                  $coreHelper
     * @param   MciHelper                   $mciHelper
     * @param   ImageDownloader             $imageDownloader
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   Filesystem                  $filesystem
     * @param   MediaGalleryProcessor       $mediaGalleryProcessor
     * @param   ReadHandler                 $configurableReadHandler
     * @param   ProductResourceFactory      $productResourceFactory
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
        ProductResourceFactory $productResourceFactory
    ) {
        parent::__construct($context);
        $this->apiConfigHelper          = $apiConfigHelper;
        $this->coreHelper               = $coreHelper;
        $this->mciHelper                = $mciHelper;
        $this->imageDownloader          = $imageDownloader;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->filesystem               = $filesystem;
        $this->mediaDirectory           = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->mediaGalleryProcessor    = $mediaGalleryProcessor;
        $this->configurableReadHandler  = $configurableReadHandler;
        $this->productResourceFactory   = $productResourceFactory;
        $this->productResource          = $productResourceFactory->create();
    }

    /**
     * Synchronizes Mirakl images (mirakl_image_* attributes) :
     *
     * 1. Download images if URL is specified
     * 2. Add images to product
     * 3. Define images as default if no image where present
     *
     * @param   Process $process
     * @param   int     $limit
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

            $collection = $this->productCollectionFactory->create();

            $conds = [];
            foreach ($attributes as $attribute) {
                /** @var EavAttribute $attribute */
                $conds[] = [
                    'attribute' => $attribute->getAttributeCode(),
                    'like'      => 'http%processed=false%',
                ];
            }
            $collection->addAttributeToFilter($conds, null, 'left');

            if ($limit) {
                $collection->getSelect()->limit($limit);
            }

            $collection->setStoreId(0);
            $collection->addAttributeToSelect('*');
            $collection->addMediaGalleryData(); // /!\ Loads collection implicitly

            $process->output(__(
                'Found %1 product%2 to process.',
                count($collection),
                count($collection) > 1 ? 's' : ''
            ));

            /** @var \Magento\Catalog\Model\Product $product */
            foreach ($collection as $product) {
                $start = microtime(true);
                $this->configurableReadHandler->execute($product);

                $process->output(__('Processing images for product %1...', $product->getId()));

                $product->setStoreId(0); // force admin area on product

                $images = [];
                $deletedImages = false;
                foreach ($attributes as $attribute) {
                    /** @var EavAttribute $attribute */
                    if (!$url = $product->getData($attribute->getAttributeCode())) {
                        continue;
                    }

                    if ($url == self::DELETED_IMAGE_URL) {
                        $product->setData($attribute->getAttributeCode(), '');
                        $deletedImages = true;
                        continue;
                    }

                    $url = $this->prepareUrl($url);

                    $process->output(__('Downloading image %1', $url));
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
                }

                if (empty($images) && !$deletedImages) {
                    continue; // No valid image to save, continue to next product
                }

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

                // Restore real image order
                $attrCode = $this->mediaGalleryProcessor->getAttribute()->getAttributeCode();
                $mediaGalleryData = $product->getData($attrCode);

                $i = count($mediaGalleryData['images']);
                foreach ($mediaGalleryData['images'] as &$image) {
                    $image['position'] = --$i;
                }
                $product->setData($attrCode, $mediaGalleryData);

                $process->output(__('Saving product %1...', $product->getId()));

                try {
                    $this->productResource->save($product);
                } catch (\Exception $e) {
                    $process->output(__('ERROR: %1', $e->getMessage()));
                }
                $process->output(__('Done!'), true);

                $time = round(microtime(true) - $start, 2);
                $process->output(__('Images processed in %1s', $time), true);
            }
        } catch (\Exception $e) {
            $process->fail(__('ERROR: %1', $e->getMessage()));
            throw $e;
        }

        return $this;
    }

    /**
     * Prepares URL before being downloaded
     * (remove the 'processed' query parameter that has been added by the connector).
     *
     * @param   string  $url
     * @return  string
     */
    protected function prepareUrl($url)
    {
        $queryParams = [];

        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $queryParams);
        unset($queryParams['processed']);
        $urlParts['query'] = http_build_query($queryParams) ?: null;
        $url = $this->coreHelper->buildUrl($urlParts);

        return $url;
    }
}
