<?php
namespace Mirakl\Mci\Model\Product\Import\Adapter;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Framework\ObjectManagerInterface;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Mci\Helper\Config;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Product\Import\Category as CategoryHelper;
use Mirakl\Mci\Helper\Product\Import\Data as DataHelper;
use Mirakl\Mci\Helper\Product\Import\Finder;
use Mirakl\Mci\Helper\Product\Import\Product as ProductHelper;
use Mirakl\Mci\Helper\Product\Import\Url as UrlHelper;
use Mirakl\Mci\Model\Product\Attribute\AttributeUtil;
use Mirakl\Mci\Model\Product\Import\Exception\WarningException;
use Mirakl\Mci\Model\Product\Import\Indexer\Indexer;

class Standard implements AdapterInterface
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
     * @var MciHelper
     */
    protected $mciHelper;

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
     * @param   CoreHelper              $coreHelper
     * @param   MciHelper               $mciHelper
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
     */
    public function __construct(
        CoreHelper $coreHelper,
        MciHelper $mciHelper,
        Config $config,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        DataHelper $dataHelper,
        UrlHelper $urlHelper,
        Finder $finder,
        ProductAction $productAction,
        ProductResourceFactory $productResourceFactory,
        Indexer $indexer,
        ObjectManagerInterface $objectManager
    ) {
        $this->coreHelper             = $coreHelper;
        $this->mciHelper              = $mciHelper;
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
    }

    /**
     * @param   Product $product
     * @return  string
     */
    private function getParentProductHash(Product $product)
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
    public function import($shopId, array $data)
    {
        $this->initAuthorization();

        $dataSku = $data[MciHelper::ATTRIBUTE_SKU];
        unset($data['sku']); // do not erase Magento product SKU

        // Retrieve associated category and throw exception if not found
        $category = $this->categoryHelper->getCategoryById($data[MciHelper::ATTRIBUTE_CATEGORY]);
        unset($data[MciHelper::ATTRIBUTE_CATEGORY]);

        // Map the category column if we want to automatically assign category to the product (for API P21 export)
        if ($this->config->isAutoAssignCategory()) {
            $data['mirakl_category_id'] = $category->getId();
        }

        /**
         * Clean localizable attributes (set a value for store 0 and avoid useless overrides).
         * For example if default locale is fr_FR:
         * ['name-fr_FR' => 'Title FR', 'name-en_US' => 'Title EN']
         * becomes:
         * ['name' => 'Title FR', 'name-en_US' => 'Title EN']
         */
        $defaultLocale = $this->config->getLocale();
        foreach (array_keys($data) as $attrCode) {
            $attrInfo = AttributeUtil::parse($attrCode);
            if ($attrInfo->isLocalized() && $attrInfo->getLocale() == $defaultLocale && !isset($data[$attrInfo->getCode()])) {
                $data[$attrInfo->getCode()] = $data[$attrCode];
                unset($data[$attrCode]);
            }
        }

        $singleStore = $this->config->isSingleStoreMode();

        // Try to find a simple product
        $product = $this->finder->findProductByDeduplication($data, Product\Type::TYPE_SIMPLE);

        // Verify that if a simple product is found, it is the same as the product found by shop sku
        // Otherwise, transfer the shop sku to main product
        $productByShopSku = $this->mciHelper->findProductByShopSku($shopId, $dataSku);
        if ($this->areProductsDifferent($product, $productByShopSku)) {
            $this->mciHelper->removeProductShopSku($productByShopSku, $shopId, $dataSku);
            $this->saveProduct($productByShopSku);
        } elseif (!$product && $productByShopSku) {
            $product = $productByShopSku;
        }

        $productExists = (bool) $product;

        $this->dataHelper->updateProductImagesProcessingFlag($data, $product ?: null);

        // Update product multi values
        if ($productExists) {
            $this->dataHelper->updateProductDataMultiValues($product, $data);
        }

        // Create and save product
        if (!$productExists) {
            $product = $this->productHelper->createSimpleProduct($category, $data);
        }

        $this->mciHelper->addProductShopSku($product, $shopId, $dataSku);

        $allowUpdate = $this->config->isUpdateExistingProducts();

        // Update product if allowed
        if ($productExists && $allowUpdate) {
            $this->dataHelper->addDataToProduct($product, $data);
            $this->categoryHelper->addCategoryToProduct($product, $category);
        }

        // Try to find a parent product
        $parentProduct = null;
        if ($productExists) {
            // Check parent product availability only if simple product exists
            $parentProduct = $this->coreHelper->getParentProduct($product);
            if ($parentProduct) {
                /** @var \Magento\Catalog\Model\ResourceModel\Product $productResource */
                $productResource = $this->productResourceFactory->create();
                $productResource->load($parentProduct, $parentProduct->getId());
            }
        }

        // Try to find a parent product by variant group code
        $parentProductByVariantId = $this->finder->findParentProductByVariantId($shopId, $data);

        if (!$parentProduct && $parentProductByVariantId) {
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
            if (!$parentProduct && $this->config->isAutoCreateConfigurableProducts()) {
                $parentProduct = $this->productHelper->createConfigurableProduct($category, $data);
                foreach ($this->dataHelper->getDataVariants($data) as $code => $value) {
                    $parentProduct->setData($code, null); // Variant attributes must not have any values in parent product
                }
                $this->dataHelper->updateProductImagesProcessingFlag($data, $parentProduct);
                if ($productExists) {
                    $this->copyProductImages($product, $parentProduct);
                }
            } elseif ($parentProduct) {
                // Exclude existing product from being checked
                $excludedProductIds = $productExists ? [$product->getId()] : [];
                // Check if parent product does not have a similar variant product associated
                $this->validateParentProductVariants($parentProduct, $data, $excludedProductIds);
            }
        }

        if (!$productExists && $parentProduct) {
            // Hide variant product that should not be visible in frontend
            $product->setVisibility(Product\Visibility::VISIBILITY_NOT_VISIBLE);

            // Define a custom URL key in order to leave the nice URL key to the parent product
            $product->setUrlKey($product->formatUrlKey($product->getSku()));
        }

        // Save the product
        $this->saveProduct($product);

        if ($parentProduct) {
            $parentProductHash = $this->getParentProductHash($parentProduct);

            // Update the variant grouping code list of parent product
            $this->productHelper->addProductShopVariantId($parentProduct, $shopId, $data);

            // Add category to existing categories
            $this->categoryHelper->addCategoryToProduct($parentProduct, $category);

            // Associate variant product to parent
            $this->productHelper->associateProducts($parentProduct, $product);

            if ($allowUpdate && $product->getId() < $parentProduct->getId()) {
                // Update parent product data if it comes from the initial simple product that was used to create
                // the configurable product (simple product id is lower than parent product id)
                $this->dataHelper->addDataToProduct($parentProduct, $data);
                $this->saveProduct($parentProduct);
            } elseif ($parentProductHash != $this->getParentProductHash($parentProduct)) {
                // Save the parent product only if some data have changed
                $this->saveProduct($parentProduct);
            }

            $this->indexer->setIdsToIndex($parentProduct);
        }

        $this->indexer->setIdsToIndex($product);

        if (!$singleStore && ($allowUpdate || !$productExists)) {
            // Save product on each store using language defined in data
            $dataLocalized = []; // Format is ['fr_FR' => ['name' => 'french name', 'description' => 'french desc']]

            foreach (array_keys($data) as $attrCode) {
                $attrInfo = AttributeUtil::parse($attrCode);
                if ($attrInfo->isLocalized() && $data[$attrCode] != $data[$attrInfo->getCode()] && strlen(trim($data[$attrCode])) > 0) {
                    // Set localized data in another locale only if values are different
                    $dataLocalized[$attrInfo->getLocale()][$attrInfo->getCode()] = $data[$attrCode];
                }
            }

            if (!empty($dataLocalized)) {
                // Initialize products to update
                $productIds = [$product->getId()];
                if ($parentProduct
                    && (!$parentProductExists || ($allowUpdate && $product->getId() < $parentProduct->getId()))
                )  {
                    /**
                     * We update the parent product store view scope in the following cases:
                     *   - parent product did not exist before (has just been created)
                     *   - parent product exists, we allow update and the current simple product is the one that was
                     *     used to create the configurable product (its id is lower than the parent product id because
                     *     created just before the parent product)
                     */
                    $productIds[] = $parentProduct->getId();
                }

                // Loop on stores enabled for MCI import and save product with localized data
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

        return $this;
    }
}
