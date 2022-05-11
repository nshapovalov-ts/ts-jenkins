<?php
namespace Mirakl\Mci\Model\Product\Import\Adapter;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Authorization\Policy\DefaultPolicy;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Authorization\RoleLocatorInterface;
use Mirakl\Mci\Model\Product\Import\Exception\ImportException;

/**
 * @property \Mirakl\Core\Helper\Data $coreHelper
 * @property \Mirakl\Mci\Helper\Product\Import\Data $dataHelper
 * @property \Mirakl\Mci\Helper\Product\Import\Url $urlHelper
 * @property \Magento\Catalog\Model\ResourceModel\ProductFactory $productResourceFactory
 * @property \Magento\Framework\ObjectManagerInterface $objectManager
 */
trait AdapterTrait
{
    /**
     * Returns true if specified products are different
     *
     * @param   Product|null    $product1
     * @param   Product|null    $product2
     * @return  bool
     */
    protected function areProductsDifferent($product1, $product2)
    {
        return $product1 && $product2 && $product1->getId() != $product2->getId();
    }

    /**
     * Copy all images from $productSrc to $productDest
     *
     * @param   Product $productSrc
     * @param   Product $productDest
     */
    public function copyProductImages(Product $productSrc, Product $productDest)
    {
        $images = [];
        $fileSystem = $this->objectManager->get(\Magento\Framework\Filesystem::class);
        $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
        foreach ($productDest->getMediaAttributes() as $imageAttribute) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $imageAttribute */
            $imageAttributeCode = $imageAttribute->getAttributeCode();
            $file = $mediaPath . $productSrc->getMediaConfig()->getMediaPath($productSrc->getData($imageAttributeCode));
            if (file_exists($file)) {
                if (!isset($images[$file])) {
                    $images[$file] = [];
                }
                $images[$file][] = $imageAttributeCode;
            }
        }

        if (empty($images)) {
            /** @var \Mirakl\Mci\Helper\Data $mciHelper */
            $mciHelper = $this->objectManager->get(\Mirakl\Mci\Helper\Data::class);
            foreach (array_keys($mciHelper->getImagesAttributes()) as $attrCode) {
                if ($imageUrl = $productSrc->getData($attrCode)) {
                    $imageUrl = $this->coreHelper->addQueryParamToUrl($imageUrl, 'processed', 'false');
                    $productDest->setData($attrCode, $imageUrl);
                }
            }
        } else {
            foreach ($images as $file => $imageAttributeList) {
                try {
                    $productDest->addImageToMediaGallery($file, $imageAttributeList, false, false);
                } catch (\Exception $e) {
                    // Ignore exception
                }
            }
        }
    }

    /**
     * Need to allow current user to create/edit products if no role
     * has been defined for current context (admin, cli, cron, ...)
     */
    protected function initAuthorization()
    {
        /** @var RoleLocatorInterface $roleLocator */
        $roleLocator = $this->objectManager->get(RoleLocatorInterface::class);
        if ('' === $roleLocator->getAclRoleId()) {
            $this->objectManager->configure(['preferences' => [PolicyInterface::class => DefaultPolicy::class]]);
        }
    }

    /**
     * For each existing associated product of parent product, verify that it does
     * not have exactly the same variants than provided data.
     * If such a variant product already exists, throw an error and refuse product.
     *
     * @param   Product $parentProduct
     * @param   array   $data
     * @param   array   $excludedProductIds
     * @throws  ImportException
     */
    protected function validateParentProductVariants(Product $parentProduct, array $data, array $excludedProductIds = [])
    {
        $variants = $this->dataHelper->getDataVariants($data);

        if (empty($variants) || $parentProduct->getTypeId() != Configurable::TYPE_CODE) {
            return;
        }

        /** @var Configurable $parentProductType */
        $parentProductType = $parentProduct->getTypeInstance();
        $usedProductCollection = $parentProductType->getUsedProductCollection($parentProduct)
            ->addAttributeToSelect(array_keys($variants));

        if (!empty($excludedProductIds)) {
            $usedProductCollection->addIdFilter($excludedProductIds, true);
        }

        if ($usedProductCollection->count()) {
            /** @var Product $usedProduct */
            foreach ($usedProductCollection as $usedProduct) {
                $exists = true;
                foreach ($variants as $key => $value) {
                    if ($usedProduct->getData($key) != $value) {
                        $exists = false;
                        break; // one difference is enough
                    }
                }
                if ($exists) {
                    throw new ImportException(
                        __('A variant product already exists with the same variants as provided data.')
                    );
                }
            }
        }
    }

    /**
     * Saves specified product with duplicate URL key error handling
     *
     * @param   Product $product
     */
    protected function saveProduct(Product $product)
    {
        $this->urlHelper->generateUrlKey($product);

        /** @var \Magento\Catalog\Model\ResourceModel\Product $productResource */
        $productResource = $this->productResourceFactory->create();

        try {
            $productResource->save($product);
        } catch (\Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException $e) {
            // If URL rewrite is already used, make it unique and generate again
            $urlKey = $product->getUrlKey() . '-' . $product->getId();
            $product->setUrlKey($urlKey);
            $product->setUrlPath($urlKey);
            $productResource->save($product);
        }
    }
}
