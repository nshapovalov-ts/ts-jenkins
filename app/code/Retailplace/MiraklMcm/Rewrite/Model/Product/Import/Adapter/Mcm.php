<?php

/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\MiraklMcm\Rewrite\Model\Product\Import\Adapter;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Product\Import\Category as CategoryHelper;
use Mirakl\Mci\Helper\Product\Import\Data as DataHelper;
use Mirakl\Mci\Helper\Product\Import\Finder;
use Mirakl\Mci\Helper\Product\Import\Url as UrlHelper;
use Mirakl\Mci\Model\Product\Attribute\AttributeUtil;
use Mirakl\Mci\Model\Product\Import\Adapter\AdapterTrait;
use Mirakl\Mci\Model\Product\Import\Exception\WarningException;
use Mirakl\Mcm\Helper\Config;
use Mirakl\Mcm\Helper\Data as Helper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Mirakl\Mcm\Helper\Product\Import\Product as ProductHelper;
use Mirakl\Mcm\Model\Product\Import\Indexer\Indexer;
use Retailplace\MiraklConnector\Model\TaxClassIdAttributeUpdater;
use Exception;
use Magento\Framework\Exception\NotFoundException;

/**
 * Class Mcm
 */
class Mcm extends \Mirakl\Mcm\Model\Product\Import\Adapter\Mcm
{
    use AdapterTrait;

    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var TaxClassIdAttributeUpdater
     */
    private $taxClassIdAttributeUpdater;

    /** @var \Mirakl\Mci\Helper\Data */
    private $mciHelper;

    /**
     * MCM constructor
     *
     * @param \Mirakl\Core\Helper\Data $coreHelper
     * @param \Mirakl\Mcm\Helper\Data $helper
     * @param \Mirakl\Mcm\Helper\Config $config
     * @param \Mirakl\Mci\Helper\Product\Import\Category $categoryHelper
     * @param \Mirakl\Mcm\Helper\Product\Import\Product $productHelper
     * @param \Mirakl\Mci\Helper\Product\Import\Data $dataHelper
     * @param \Mirakl\Mci\Helper\Product\Import\Url $urlHelper
     * @param \Mirakl\Mci\Helper\Product\Import\Finder $finder
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productResourceFactory
     * @param \Mirakl\Mcm\Model\Product\Import\Indexer\Indexer $indexer
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param TaxClassIdAttributeUpdater $taxClassIdAttributeUpdater
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     */
    public function __construct(
        CoreHelper $coreHelper,
        Helper $helper,
        Config $config,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        DataHelper $dataHelper,
        UrlHelper $urlHelper,
        Finder $finder,
        ProductAction $productAction,
        ProductResourceFactory $productResourceFactory,
        Indexer $indexer,
        ObjectManagerInterface $objectManager,
        EventManagerInterface $eventManager,
        ExtensionAttributesFactory $extensionAttributesFactory,
        TaxClassIdAttributeUpdater $taxClassIdAttributeUpdater,
        MciHelper $mciHelper
    ) {
        parent::__construct(
            $coreHelper,
            $helper,
            $config,
            $categoryHelper,
            $productHelper,
            $dataHelper,
            $urlHelper,
            $finder,
            $productAction,
            $productResourceFactory,
            $indexer,
            $objectManager,
            $eventManager
        );

        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->taxClassIdAttributeUpdater = $taxClassIdAttributeUpdater;
        $this->mciHelper = $mciHelper;
    }

    /**
     * {@inheritdoc}
     * @throws NotFoundException
     * @throws Exception
     */
    public function import(array $data)
    {
        $this->initAuthorization();

        $this->updateSkuAttribute($data);

        $category = $this->validateCategory($data);

        $this->cleanLocalizableAttributes($data);

        // Try to find a simple product
        $product = $this->findSimpleProductByDeduplication($data);
        $productExists = (bool) $product;
        $unlinkProductId = null;

        $this->dataHelper->updateProductImagesProcessingFlag($data, $product ?: null);
        $this->markImagesToBeProcessed($data, $product ?: null);
        $this->updateProductTaxClass($data);

        if (!$productExists) {
            // Create and save product
            $product = $this->productHelper->createSimpleProduct($category, $data);
            $productExists = (bool) $product;
        } else {
            $oldVGC = $product->getData(Helper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE);
            $newVGC = $data[Helper::CSV_MIRAKL_VARIANT_GROUP_CODE] ?? '';
            if (strlen($oldVGC) && $newVGC != $oldVGC) {
                $unlinkProductId = $product->getId();
            }
        }

        $this->productHelper->addProductVariantId($product, $data);

        // Update product if allowed
        if ($productExists) {
            $this->updateBestSeller($product, $data);
            $this->dataHelper->addDataToProduct($product, $data, false);
            $this->addCategoryToProduct($product, $category);
        }

        // Try to find a parent product
        $parentProduct = null;
        if ($productExists) {
            // Check parent product availability only if simple product exists
            $parentProduct = $this->coreHelper->getParentProduct($product);
            if ($parentProduct) {
                $this->productResourceFactory->create()->load($parentProduct, $parentProduct->getId());
            }
        }

        // Try to find a parent product by variant group code
        $parentProductByVariantId = $this->findParentProductByVariantId($data);

        if (!$parentProduct && $parentProductByVariantId && $parentProductByVariantId->getId() !== null) {
            $parentProduct = $parentProductByVariantId;
        } elseif ($this->areProductsDifferent($parentProduct, $parentProductByVariantId)) {
            throw new WarningException(__(
                "Simple product '%1' has 2 matching configurable products: '%2' (Magento)" .
                " and '%3' (import file). Please resolve manually.",
                $product->getSku(),
                $parentProduct->getSku(),
                $parentProductByVariantId->getSku()
            ));
        }

        $parentProductExists = (bool) $parentProduct;

        if ($this->dataHelper->isDataHaveVariants($data)) {
            // Create parent product if simple product has variant attributes
            if (!$parentProduct) {
                $parentProduct = $this->productHelper->createConfigurableProduct($category, $data);
                $this->dataHelper->updateProductImagesProcessingFlag($data, $parentProduct);
                $this->markImagesToBeProcessed($data, $parentProduct);
                if ($productExists) {
                    $this->copyProductImages($product, $parentProduct);
                }
            } else {
                // Exclude existing product from being checked
                $excludedProductIds = $productExists ? [$product->getId()] : [];
                // Check if parent product does not have a similar variant product associated
                $this->validateParentProductVariants($parentProduct, $data, $excludedProductIds);

                if ($productExists && $product->getId() < $parentProduct->getId()) {
                    // Update parent product data if it comes from the initial simple product that was used to create
                    // the configurable product (simple product id is lower than parent product id)
                    $this->updateBestSeller($parentProduct, $data);
                    $this->dataHelper->addDataToProduct($parentProduct, $data);
                }
            }

            // Variant attributes must not have any values in parent product
            foreach ($this->dataHelper->getDataVariants($data) as $code => $value) {
                $parentProduct->setData($code, null);
            }
        }

        if ($productExists && empty($data[MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE])) {
            // Handle products that do not have VGC anymore
            $product->setVisibility($this->config->getDefaultVisibility());
        }
        // Save the product
        $this->saveProduct($product);

        $this->indexer->setIdsToIndex($product);

        $this->processParentProduct($parentProduct, $data, $category, $unlinkProductId);

        $singleStore = $this->config->isSingleStoreMode();

        // Save product on each store using language defined in data
        $dataLocalized = []; // Format is ['fr_FR' => ['name' => 'french name', 'description' => 'french desc']]

        if (!$singleStore && !$productExists) {
            foreach (array_keys($data) as $attrCode) {
                if (0 === strpos($attrCode, 'mirakl-')) {
                    continue; // Mirakl attributes do not have to be parsed
                }
                $attrInfo = AttributeUtil::parse($attrCode);
                if ($attrInfo->isLocalized() && $data[$attrCode] != $data[$attrInfo->getCode()] && strlen(trim($data[$attrCode])) > 0) {
                    // Set localized data in another locale only if values are different
                    $dataLocalized[$attrInfo->getLocale()][$attrInfo->getCode()] = $data[$attrCode];
                }
            }

            if (!empty($dataLocalized)) {
                // Initialize products to update
                $productIds = [$product->getId()];
                if ($parentProduct && !$parentProductExists) {
                    $productIds[] = $parentProduct->getId();
                }

                // Loop on stores enabled for MCM import and save product with localized data
                foreach ($this->config->getStoresUsedForProductImport() as $store) {
                    $locale = $this->config->getLocale($store);
                    if (!isset($dataLocalized[$locale])) {
                        continue;
                    }

                    // Set value for URL key if name is localized
                    if (isset($dataLocalized[$locale]['name'])) {
                        $dataLocalized[$locale]['url_key'] = $product->formatUrlKey($dataLocalized[$locale]['name']);
                    }

                    // Update product attributes in fast mode
                    $this->productAction->updateAttributes($productIds, $dataLocalized[$locale], $store->getId());

                    // If name is localized, update URL rewrites for this store as well
                    if (isset($dataLocalized[$locale]['name'])) {
                        foreach ($productIds as $productId) {
                            $this->urlHelper->refreshProductUrlRewrites($productId, $store->getId());
                        }
                    }
                }
            }
        }

        if ($parentProductExists) {
            $this->eventManager->dispatch('mirakl_mcm_update_parent_product', [
                'parent_product'         => $parentProduct,
                'product'                => $product,
                'product_data'           => $data,
                'product_data_localized' => $dataLocalized,
            ]);
        }

        return $product;
    }

    /**
     * Replace product category
     *
     * @param ProductInterface $product
     * @param CategoryInterface $category
     * @return ProductInterface
     */
    private function addCategoryToProduct(ProductInterface $product, CategoryInterface $category)
    {
        $product->setCategoryIds($this->getCategoriesExceptSameParent($product, $category));
        // used to call Vdcstore plugin
        $this->categoryHelper->addCategoryToProduct($product, $category);

        return $product;
    }

    /**
     * Process parent product import
     *
     * @param   Product     $parentProduct
     * @param   array       $data
     * @param   Category    $category
     * @param   int|null    $unlinkProductId
     * @throws Exception
     */
    public function processParentProduct($parentProduct, $data, $category, $unlinkProductId = null)
    {
        if (!$parentProduct) {
            return;
        }

        // Update the variant group code of parent product
        if (!empty($data[Helper::CSV_MIRAKL_VARIANT_GROUP_CODE])) {
            $parentProduct = $this->productHelper->addProductVariantId($parentProduct, $data);
        }

        $parentProduct->setCategoryIds($this->getCategoriesExceptSameParent($parentProduct, $category));

        // Add category to existing categories
        $this->categoryHelper->addCategoryToProduct($parentProduct, $category);

        // Associate variant products
        $this->processVariantProducts($parentProduct, $unlinkProductId);
        $parentProduct->setStoreId(Store::DEFAULT_STORE_ID);
        // Save parent product
        $this->saveProduct($parentProduct);

        // Schedule parent product reindex
        $this->indexer->setIdsToIndex($parentProduct);
    }

    /**
     * Get list of product categories excluding siblings (with the same parent) of a category from 2nd parameter
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     * @return array
     */
    private function getCategoriesExceptSameParent(ProductInterface $product, CategoryInterface $category)
    {
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create();
        }
        $extensionAttributes->setCategoryLinks(null);
        $product->setExtensionAttributes($extensionAttributes);
        $categoryIds = [];
        $pathIds = $category->getPathIds();
        $replaceParentId = $pathIds[1] ?? null;
        foreach ($product->getCategoryCollection() as $existCategory) {
            $pathIds = $existCategory->getPathIds();
            if (isset($pathIds[1]) && $pathIds[1] != $replaceParentId) {
                $categoryIds[] = $existCategory->getId();
            }
        }

        return array_unique($categoryIds);
    }

    /**
     * Update Mirakl best seller
     *
     * @param Product $product
     * @param array $data
     */
    private function updateBestSeller(Product $product, array &$data)
    {
        $product->setData('mirakl_best_seller', $data['best_seller']);
        if (isset($data['best_seller']) && $product->getData('best_seller')) {
            $data['best_seller'] = true;
        }
    }

    /**
     * Update Product Tax Class
     *
     * @param array $data
     */
    private function updateProductTaxClass(array &$data)
    {
        $attributeGstExemptOptionId = $this->taxClassIdAttributeUpdater->getGstExemptOptionId();
        $taxClassId = $this->taxClassIdAttributeUpdater->getTaxClassId("Taxable Goods");
        $value = !empty($data[TaxClassIdAttributeUpdater::ATTRIBUTE_CODE_GST_EXEMPT]) ? $data[TaxClassIdAttributeUpdater::ATTRIBUTE_CODE_GST_EXEMPT] : 0;

        if (empty($value) || ($value > 0 && $value != $attributeGstExemptOptionId)) {
            $data[TaxClassIdAttributeUpdater::ATTRIBUTE_CODE_TAX_CLASS] = $taxClassId;
        } else {
            $data[TaxClassIdAttributeUpdater::ATTRIBUTE_CODE_TAX_CLASS] = 0;
        }
    }

    /**
     * Mark product images to be processed
     *
     * @param array $data
     * @param \Magento\Catalog\Model\Product|null $product
     */
    private function markImagesToBeProcessed(&$data, ProductModel $product = null)
    {
        $productExists = (bool) $product;
        $imageAttributes = $this->mciHelper->getImagesAttributes();
        foreach ($imageAttributes as $imageAttribute) {
            /** @var EavAttribute $imageAttribute */
            $attrCode = $imageAttribute->getAttributeCode();
            if (isset($data[$attrCode]) && $data[$attrCode]) {
                $testUrl = $this->coreHelper->addQueryParamToUrl($data[$attrCode], 'processed', 'true');
                if (!$productExists || $product->getData($attrCode) != $testUrl) {
                    $data['non_processed_' . $attrCode] = true;
                }
            }
        }
    }
}
