<?php
namespace Mirakl\Catalog\Helper;

use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product as ProductObject;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Magento\Framework\Search\Request\Dimension;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Api\Helper\Product as Api;
use Mirakl\Catalog\Helper\Config as CatalogConfig;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Connector\Common\ExportTrait;
use Mirakl\Core\Helper\Data as CoreHelper;
use Mirakl\Process\Model\Process;

class Product extends AbstractHelper implements ExportInterface
{
    use ExportTrait;

    const EXPORT_SOURCE = 'P21';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var MediaConfig
     */
    protected $productMediaConfig;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryResourceFactory
     */
    protected $categoryResourceFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var ProductResourceFactory
     */
    protected $productResourceFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfig;

    /**
     * @var CoreHelper
     */
    protected $coreHelper;

    /**
     * @var IndexScopeResolver
     */
    protected $tableResolver;

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   CategoryFactory             $categoryFactory
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   CategoryCollectionFactory   $categoryCollectionFactory
     * @param   ProductResourceFactory      $productResourceFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   ProductHelper               $productHelper
     * @param   Api                         $api
     * @param   CatalogConfig               $catalogConfig
     * @param   MediaConfig                 $productMediaConfig
     * @param   CoreHelper                  $coreHelper
     * @param   IndexScopeResolver          $tableResolver
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductResourceFactory $productResourceFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductHelper $productHelper,
        Api $api,
        CatalogConfig $catalogConfig,
        MediaConfig $productMediaConfig,
        CoreHelper $coreHelper,
        IndexScopeResolver $tableResolver
    ) {
        parent::__construct($context);
        $this->storeManager              = $storeManager;
        $this->categoryFactory           = $categoryFactory;
        $this->categoryResourceFactory   = $categoryResourceFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productResourceFactory    = $productResourceFactory;
        $this->productCollectionFactory  = $productCollectionFactory;
        $this->productHelper             = $productHelper;
        $this->api                       = $api;
        $this->catalogConfig             = $catalogConfig;
        $this->productMediaConfig        = $productMediaConfig;
        $this->coreHelper                = $coreHelper;
        $this->tableResolver             = $tableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $data)
    {
        if (!$this->isExportable()) {
            return false;
        }

        return $this->api->export($data);
    }

    /**
     * Export Magento products to Mirakl platform
     *
     * @param   Process $process
     * @return  int
     */
    public function exportAll(Process $process = null)
    {
        if (!$this->isExportable()) {
            if ($process) {
                $process->output(__('Export has been blocked by another module.'));
            }

            return false;
        }

        if ($process) {
            $process->output(__('Preparing products to export...'));
        }

        $products = $this->getProductsToExport();
        if (!$products->getSize()) {
            $process->output(__('Nothing to export'));

            return false;
        }

        $synchroId = $this->exportCollection($products);

        if ($process) {
            $process->setSynchroId($synchroId);
            $process->output(__('Done! (%1)', $synchroId), true);
        }

        return $synchroId;
    }

    /**
     * Exports custom product collection to Mirakl platform
     *
     * @param   ProductCollection   $collection
     * @param   null|string         $action
     * @return  int
     */
    public function exportCollection(ProductCollection $collection, $action = null)
    {
        $this->_eventManager->dispatch(
            'mirakl_catalog_export_product_collection_prepare_before',
            ['collection' => $collection]
        );

        $data = [];
        foreach ($collection as $product) {
            $data[] = $this->prepare($product, $action);
        }

        return $this->export($data);
    }

    /**
     * Retrieves Magento products available for being exported to Mirakl platform
     *
     * @return  ProductCollection
     */
    public function getProductsToExport()
    {
        $store = $this->getStore();

        // 1. Inititalize product collection
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($store);

        // 2. Retrieve category ids that have mirakl_sync attribute defined to Yes
        $categoryIds = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('mirakl_sync', 1)
            ->getAllIds();

        // 3. Get product ids of categories retrieved above (use index table to handle anchor categories)
        $productIds = [];
        if (!empty($categoryIds)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Category $resource */
            $resource = $this->categoryResourceFactory->create();
            $connection = $resource->getConnection();

            $dimension = new Dimension(Store::ENTITY, $store->getId());

            $categoryIndex = $this->tableResolver->resolve(
                'catalog_category_product_index', [$dimension]
            );

            if (!$connection->isTableExists($categoryIndex)) {
                $categoryIndex = $resource->getTable('catalog_category_product_index');
            }

            $select = $connection->select()
                ->from($categoryIndex, 'product_id')
                ->where('category_id IN (?)', $categoryIds);

            $productIds = array_unique($connection->fetchCol($select));
        }

        if (empty($productIds)) {
            $collection->addIdFilter([0]); // Workaround for empty collection
        }

        // 4. Add some conditions and information to the product collection
        $collection->addAttributeToSelect('*')
            ->addFieldToFilter('type_id', 'simple')
            ->addAttributeToFilter('mirakl_sync', 1);

        $this->_eventManager->dispatch('mirakl_get_products_to_export_after', [
            'collection' => $collection,
            'category_ids' => $categoryIds,
        ]);

        return $collection;
    }

    /**
     * @return Store
     */
    public function getStore()
    {
        return $this->storeManager->getStore($this->catalogConfig->getStoreId());
    }

    /**
     * Prepares product data for export
     *
     * @param   DataObject  $product
     * @param   null|string $action
     * @return  array
     */
    public function prepare(DataObject $product, $action = null)
    {
        /** @var ProductObject $product */
        if (null === $action) {
            $action = !$product->getId() || $product->getData('mirakl_sync') ? 'update' : 'delete';
        }

        $active = $product->getStatus() == ProductStatus::STATUS_ENABLED;

        if ($action === 'delete') {
            return [
                'product-sku'   => $product->getSku(),
                'update-delete' => $action,
            ];
        }

        $image = '';
        if ($product->getImage()) {
            $image = $this->productMediaConfig->getMediaUrl($product->getImage());
        }

        $description = $this->coreHelper->truncate(strip_tags($product->getData('description')), 2000);
        $title = $this->coreHelper->truncate($product->getName(), 255);
        $brand = $this->coreHelper->truncate((string) $this->getProductBrand($product), 255);

        return [
            'product-sku'         => $product->getSku(),
            'product-description' => $description,
            'product-title'       => $title,
            'category-code'       => $this->getAssociatedCategoryId($product),
            'active'              => $active ? 'true' : 'false',
            'product-references'  => \Mirakl\refs_to_query_param($this->getProductReferences($product)),
            'shop-skus'           => (string) $product->getData('mirakl_shops_skus'),
            'brand'               => $brand,
            'update-delete'       => $action,
            'product-url'         => $this->getProductUrl($product, $this->storeManager->getDefaultStoreView()),
            'media-url'           => $image,
            'authorized-shop-ids' => $this->getProductAuthorizedShopIds($product),
            'variant-group-code'  => $this->getProductVariantCode($product),
        ];
    }

    /**
     * Returns category id associated with specified Mirakl product
     *
     * @param   ProductObject   $product
     * @return  string
     * @throws  \Exception
     */
    public function getAssociatedCategoryId(ProductObject $product)
    {
        $categoryId = '';
        if ($product->getData('mirakl_category_id')) {
            // Get the category if directly defined in Mirakl tab
            $categoryId = $product->getData('mirakl_category_id');
        } elseif ($categoryIds = $product->getCategoryIds()) {
            // Try to get the category from associated categories (get the deeper category) with a root id

            /** @var CategoryCollection $collection */
            $collection = $this->categoryCollectionFactory->create();

            $collection
                ->addIdFilter($categoryIds)
                ->addIsActiveFilter()
                ->addAttributeToFilter('mirakl_sync', 1);

            $categoryId = $collection->getConnection()
                ->fetchOne($collection->getSelect()->order('level DESC'));
        }

        return $categoryId;
    }

    /**
     * Returns authorized shop ids of specified product as string
     *
     * @param   ProductObject   $product
     * @return  string
     */
    public function getProductAuthorizedShopIds(ProductObject $product)
    {
        $shopIds = $product->getData('mirakl_authorized_shop_ids');
        if (is_array($shopIds)) {
            $shopIds = implode(',', $shopIds);
        }

        return (string) $shopIds;
    }

    /**
     * Retrieves product brand if available
     * Brand attribute is configured in Mirakl configuration section:
     * "System > Configuration > Mirakl Connector > Product Attributes > Brand Attribute"
     *
     * @param   ProductObject  $product
     * @return  string|null
     */
    public function getProductBrand(ProductObject $product)
    {
        $brand = null;
        $attrCode = $this->catalogConfig->getBrandAttributeCode();
        if ($attrCode) {
            $attribute = $this->productResourceFactory->create()->getAttribute($attrCode);
            if ($attribute) {
                $brand = $product->getData($attrCode);
                if ($this->coreHelper->isAttributeUsingOptions($attribute)) {
                    // If attribute uses options, retrieve option label
                    $brand = $attribute->getSource()->getOptionText($brand);
                }
            }
        }

        return $brand;
    }

    /**
     * Retrieves product references if available
     * Attribute identifiers used for references are configured in Mirakl configuration section:
     * "System > Configuration > Mirakl Connector > Product Attributes > Identifier Attributes"
     *
     * @param   ProductObject  $product
     * @return  array
     */
    public function getProductReferences(ProductObject $product)
    {
        $refs = new DataObject();

        $this->_eventManager->dispatch('mirakl_catalog_product_references', [
            'product' => $product,
            'refs'    => $refs,
        ]);

        return $refs->getData();
    }

    /**
     * Retrieves product variant code if possible
     *
     * @param   ProductObject  $product
     * @return  string
     */
    public function getProductVariantCode(ProductObject $product)
    {
        if ($product->getTypeId() == 'simple') {
            $parent = $this->coreHelper->getParentProduct($product);
            if ($parent) {
                return $parent->getSku(); // return parent product sku as variant code
            }
        }

        return '';
    }

    /**
     * Returns product URL for specified store
     *
     * @param   ProductObject   $product
     * @param   mixed           $store
     * @return  string
     * @throws  \Exception
     */
    protected function getProductUrl(ProductObject $product, $store = null)
    {
        if (!$product->isVisibleInSiteVisibility() && !$product->isComposite()) {
            $parent = $this->coreHelper->getParentProduct($product);
            if ($parent) {
                $product = $parent; // replace simple product that is not visible individually by its parent
            }
        }

        if (empty($store)) {
            return $this->productHelper->getProductUrl($product);
        }

        $store = $this->storeManager->getStore($store);

        return $product->setStoreId($store->getId())->getUrlInStore();
    }
}
