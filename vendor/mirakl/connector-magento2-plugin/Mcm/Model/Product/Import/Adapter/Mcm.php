<?php
namespace Mirakl\Mcm\Model\Product\Import\Adapter;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;
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
use Mirakl\Mcm\Helper\Product\Import\Product as ProductHelper;
use Mirakl\Mcm\Model\Product\Import\Indexer\Indexer;

class Mcm implements AdapterInterface
{
    use AdapterTrait;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var CategoryHelper
     */
    protected $categoryHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @var ProductAction
     */
    protected $productAction;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @param   CoreHelper              $coreHelper
     * @param   Helper                  $helper
     * @param   Config                  $config
     * @param   CategoryHelper          $categoryHelper
     * @param   ProductHelper           $productHelper
     * @param   DataHelper              $dataHelper
     * @param   UrlHelper               $urlHelper
     * @param   Finder                  $finder
     * @param   ProductAction           $productAction
     * @param   ProductResourceFactory  $productResourceFactory
     * @param   Indexer                 $indexer
     * @param   ObjectManagerInterface  $objectManager
     * @param   EventManagerInterface   $eventManager
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
        EventManagerInterface $eventManager
    ) {
        $this->coreHelper             = $coreHelper;
        $this->helper                 = $helper;
        $this->config                 = $config;
        $this->categoryHelper         = $categoryHelper;
        $this->productHelper          = $productHelper;
        $this->dataHelper             = $dataHelper;
        $this->urlHelper              = $urlHelper;
        $this->finder                 = $finder;
        $this->productAction          = $productAction;
        $this->productResourceFactory = $productResourceFactory;
        $this->indexer                = $indexer;
        $this->objectManager          = $objectManager;
        $this->eventManager           = $eventManager;
    }

    /**
     * Updates SKU info from data
     *
     * @param   array   $data
     */
    public function updateSkuAttribute(&$data)
    {
        $data[Helper::ATTRIBUTE_MIRAKL_PRODUCT_ID] = $data[Helper::CSV_MIRAKL_PRODUCT_ID];
        unset($data[Helper::CSV_MIRAKL_PRODUCT_ID]);
        unset($data['sku']); // do not erase Magento product SKU
    }

    /**
     * Find parent product with a variantId
     *
     * @param   array   $data
     * @return  Product|null
     */
    public function findParentProductByVariantId(&$data)
    {
        $parentProduct = null;

        $attrCode = MciHelper::ATTRIBUTE_VARIANT_GROUP_CODE;
        if ($attrCode && isset($data[$attrCode]) && strlen($data[$attrCode])) {
            $parentProduct = $this->helper->findMcmProductByVariantId(
                $data[$attrCode], Configurable::TYPE_CODE
            );
        }

        return $parentProduct;
    }

    /**
     * Process parent product import
     *
     * @param   Product     $parentProduct
     * @param   array       $data
     * @param   Category    $category
     * @param   int|null    $unlinkProductId
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

        // Add category to existing categories
        $this->categoryHelper->addCategoryToProduct($parentProduct, $category);

        // Associate variant products
        $this->processVariantProducts($parentProduct, $unlinkProductId);

        // Save parent product
        $this->saveProduct($parentProduct);

        // Schedule parent product reindex
        $this->indexer->setIdsToIndex($parentProduct);
    }

    /**
     * Will associate variant products to specified parent product and will update according URL rewrites
     *
     * @param   Product     $parentProduct
     * @param   int|null    $unlinkProductId
     */
    public function processVariantProducts($parentProduct, $unlinkProductId = null)
    {
        if (!$parentProduct) {
            return;
        }

        $variantId = $parentProduct->getData(Helper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE);

        if (empty($variantId)) {
            return;
        }

        $variants = $this->helper->findProductsByVariantId($variantId, Product\Type::TYPE_SIMPLE);
        $variantIds = $variants->getAllIds();

        if (empty($variantIds)) {
            return;
        }

        // Associate variant products to parent
        $this->productHelper->associateProductIds($parentProduct, $variantIds, $unlinkProductId);

        foreach ($variants as $variant) {
            /** @var Product $variant */
            foreach ($this->config->getStoresUsedForProductImport() as $store) {
                $this->productAction->updateAttributes([$variant->getId()], [
                    'visibility' => Product\Visibility::VISIBILITY_NOT_VISIBLE,
                    'url_key'    => '',
                    'url_path'   => '',
                ], $store->getId());

                $this->urlHelper->deleteProductUrlRewrites($variant->setStoreId($store->getId()));
            }
            $this->indexer->setIdsToIndex($variant);
        }
    }

    /**
     * Clean localizable attributes (set a value for store 0 and avoid useless overrides).
     * For example if default locale is fr_FR:
     * ['name-fr_FR' => 'Title FR', 'name-en_US' => 'Title EN']
     * becomes:
     * ['name' => 'Title FR', 'name-en_US' => 'Title EN']
     *
     * @param   array   $data
     */
    protected function cleanLocalizableAttributes(&$data)
    {
        $defaultLocale = $this->config->getLocale();
        foreach (array_keys($data) as $attrCode) {
            $attrInfo = AttributeUtil::parse($attrCode);
            if ($attrInfo->isLocalized() && $attrInfo->getLocale() == $defaultLocale && !isset($data[$attrInfo->getCode()])) {
                $data[$attrInfo->getCode()] = $data[$attrCode];
                unset($data[$attrCode]);
            }
        }
    }

    /**
     * Find a simple product by deduplication.
     * Verify that if a simple product is found, it is the same as the product found by shop SKU.
     * Otherwise, transfer the shop sku to main product.
     *
     * @param   array   $data
     * @return  Product|null
     */
    public function findSimpleProductByDeduplication($data)
    {
        $product = $this->helper->findSimpleProductByDeduplication($data[Helper::ATTRIBUTE_MIRAKL_PRODUCT_ID]);

        if (!$product && !empty($data[Helper::CSV_MIRAKL_PRODUCT_SKU])) {
            $productByMiraklProductSku = $this->helper->findProductBySku($data[Helper::CSV_MIRAKL_PRODUCT_SKU]);
            if ($productByMiraklProductSku) {
                $product = $productByMiraklProductSku;
            }
        }

        return $product;
    }

    /**
     * @param   Product $product
     * @return  string
     */
    protected function getParentProductHash(Product $product)
    {
        $productLinks = $product->getExtensionAttributes()->getConfigurableProductLinks();

        return sha1(json_encode([
            $product->getData(MciHelper::ATTRIBUTE_VARIANT_GROUP_CODES),
            $product->getCategoryIds(),
            is_array($productLinks) ? array_unique(array_values($productLinks)) : [],
        ]));
    }

    /**
     * {@inheritdoc}
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

        if (!$productExists) {
            // Create and save product
            $product = $this->productHelper->createSimpleProduct($category, $data);
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
            $this->dataHelper->addDataToProduct($product, $data, false);
            $this->categoryHelper->addCategoryToProduct($product, $category);
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

        if (!$singleStore) {
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
                'parent_product' => $parentProduct,
                'product' => $product,
                'product_data' => $data,
                'product_data_localized' => $dataLocalized,
            ]);
        }

        return $product;
    }

    /**
     * Validate category data for product
     *
     * @param   array   $data
     * @return  Category
     * @throws  NotFoundException
     */
    protected function validateCategory(&$data)
    {
        // Retrieve associated category and throw exception if not found
        if (!isset($data[MciHelper::ATTRIBUTE_CATEGORY])) {
            throw new NotFoundException(__('Could not find "category" column in product data'));
        }

        $category = $this->categoryHelper->getCategoryById($data[MciHelper::ATTRIBUTE_CATEGORY]);
        unset($data[MciHelper::ATTRIBUTE_CATEGORY]);
        $data['mirakl_category_id'] = $category->getId();

        return $category;
    }
}
