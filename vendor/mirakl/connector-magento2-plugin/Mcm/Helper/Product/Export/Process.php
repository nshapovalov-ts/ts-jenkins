<?php
namespace Mirakl\Mcm\Helper\Product\Export;

use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Api\Helper\Mcm\Product as Api;
use Mirakl\Core\Model\ResourceModel\Product\Collection as ProductCollection;
use Mirakl\Core\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Hierarchy as HierarchyHelper;
use Mirakl\Mcm\Helper\Data as McmHelper;
use Mirakl\Mcm\Helper\Product\Export\Category as CategoryHelper;
use Mirakl\Mcm\Helper\Product\Export\Product as ProductHelper;
use Mirakl\Process\Model\Process as ProcessModel;

class Process extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryHelper
     */
    protected $categoryHelper;

    /**
     * @var ProductHelper
     */
    protected $productHelper;

    /**
     * @var CategoryResourceFactory
     */
    protected $categoryResourceFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var HierarchyHelper
     */
    protected $hierarchyHelper;

    /**
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @param   Context                     $context
     * @param   StoreManagerInterface       $storeManager
     * @param   CategoryResourceFactory     $categoryResourceFactory
     * @param   ProductCollectionFactory    $productCollectionFactory
     * @param   CategoryHelper              $categoryHelper
     * @param   ProductHelper               $productHelper
     * @param   HierarchyHelper             $hierarchyHelper
     * @param   MciHelper                   $mciHelper
     * @param   Api                         $api
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CategoryResourceFactory $categoryResourceFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryHelper $categoryHelper,
        ProductHelper $productHelper,
        HierarchyHelper $hierarchyHelper,
        MciHelper $mciHelper,
        Api $api
    ) {
        parent::__construct($context);
        $this->storeManager             = $storeManager;
        $this->categoryResourceFactory  = $categoryResourceFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryHelper           = $categoryHelper;
        $this->productHelper            = $productHelper;
        $this->hierarchyHelper          = $hierarchyHelper;
        $this->mciHelper                = $mciHelper;
        $this->api                      = $api;
    }

    /**
     * @param   ProcessModel    $process
     * @return  int|false
     */
    public function exportAll(ProcessModel $process)
    {
        $process->output(__('Preparing products to export...'));

        $products = $this->getProductsToExport();
        if (!$products->getSize()) {
            $process->output(__('Nothing to export'));

            return false;
        }

        $process->output(__('Sending products to Mirakl...'));
        $synchroId = $this->exportCollection($products);

        $process->setSynchroId($synchroId);
        $process->output(__('Done! (tracking id: %1)', $synchroId), true);

        return $synchroId;
    }

    /**
     * Exports an unique product to Mirakl platform
     *
     * @param   int     $productId
     * @param   string  $acceptance
     * @return  int|false
     */
    public function exportProduct($productId, $acceptance = ProductAcceptance::STATUS_ACCEPTED)
    {
        return $this->exportProducts([$productId], $acceptance);
    }

    /**
     * Exports specified product ids to Mirakl platform
     *
     * @param   array   $productIds
     * @param   string  $acceptance
     * @param   bool    $forceOperatorMaster
     * @return  int|false
     */
    public function exportProducts(
        array $productIds,
        $acceptance = ProductAcceptance::STATUS_ACCEPTED,
        $forceOperatorMaster = false
    ) {
        if (empty($productIds)) {
            return false;
        }

        // Retrieve products data as array
        $products = $this->productHelper->getProductsData($productIds);

        $data = [];
        foreach ($products as $product) {
            $data[] = $this->prepare($product, $acceptance, $forceOperatorMaster);
        }

        return $this->api->export($data);
    }

    /**
     * Exports custom product collection to Mirakl platform
     *
     * @param   ProductCollection   $collection
     * @param   string              $acceptance
     * @param   bool                $forceOperatorMaster
     * @return  int|false
     */
    public function exportCollection(
        ProductCollection $collection,
        $acceptance = ProductAcceptance::STATUS_ACCEPTED,
        $forceOperatorMaster = false
    ) {
        $this->_eventManager->dispatch('mirakl_export_mcm_products_before', ['collection' => $collection]);

        return $this->exportProducts($collection->getAllIds(), $acceptance, $forceOperatorMaster);
    }

    /**
     * Retrieves Magento MCM products available for being exported to Mirakl platform
     *
     * @return  ProductCollection
     */
    public function getProductsToExport()
    {
        // 1. Inititalize product collection
        $collection = $this->productCollectionFactory->create();

        // 2. Retrieve category ids from MCI catalog categories
        $categoryIds = $this->hierarchyHelper->getTree()->getCollection()->getAllIds();

        // 3. Get product ids of categories retrieved above (use index table to handle anchor categories)
        $productIds = [];
        if (!empty($categoryIds)) {
            /** @var \Magento\Catalog\Model\ResourceModel\Category $resource */
            $resource   = $this->categoryResourceFactory->create();
            $connection = $resource->getConnection();
            $select     = $connection->select()
                ->from($resource->getTable('catalog_category_product'), 'product_id')
                ->where('category_id IN (?)', $categoryIds);
            $productIds = array_unique($connection->fetchCol($select));
        }

        if (empty($productIds)) {
            $productIds = [0]; // Workaround for empty collection
        }

        // 4. Add some conditions to the product collection
        $collection->addIdFilter($productIds)
            ->addFieldToFilter('type_id', 'simple')
            ->addFieldToFilter('status', 1)
            ->addAttributeToFilter('mirakl_sync', 1)
            ->addAttributeToFilter(McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER, 1)
        ;

        $this->_eventManager->dispatch('mirakl_get_mcm_products_to_export_after', [
            'collection'   => $collection,
            'category_ids' => $categoryIds,
        ]);

        return $collection;
    }

    /**
     * @param   array   $product
     * @return  bool
     */
    protected function isSyncProduct(array $product)
    {
        // Consider that mirakl_sync is enabled if provided data do not contain the mirakl_sync key
        return !isset($product['mirakl_sync']) || $product['mirakl_sync'];
    }

    /**
     * @param   array   $product
     * @return  bool
     */
    protected function isOperatorMasterProduct(array $product)
    {
        // Consider that mirakl_mcm_is_operator_master is enabled if provided data do not contain the mirakl_mcm_is_operator_master key
        return !isset($product[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER])
            || $product[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER];
    }

    /**
     * Prepares product data for export
     *
     * @param   array   $product
     * @param   string  $acceptance
     * @param   bool    $forceOperatorMaster
     * @return  array
     */
    public function prepare(
        array $product,
        $acceptance = ProductAcceptance::STATUS_ACCEPTED,
        $forceOperatorMaster = false
    ) {
        $result = [
            'mirakl_product_id' => $product[McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID], // if null = creation else update
            'product_sku'       => $product[McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_SKU],
        ];

        $isSyncProduct = $this->isSyncProduct($product);
        $isOperatorMaster = $this->isOperatorMasterProduct($product);

        // Do not send internal Magento data to Mirakl
        unset($product[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER], $product['mirakl_sync']);

        if ($forceOperatorMaster ||
            ($isSyncProduct && $isOperatorMaster && $acceptance == ProductAcceptance::STATUS_ACCEPTED)
        ) {
            // Add product's data
            $result['data'] = $this->prepareProductData($product);
        }

        if (!$isSyncProduct) {
            // Flag product as rejected in MCM if product is not flagged for sync
            $acceptance = ProductAcceptance::STATUS_REJECTED;
        }

        if ($result['mirakl_product_id'] || $acceptance == ProductAcceptance::STATUS_REJECTED) {
            // Send acceptance value only if a mirakl_product_id is defined on product
            $result['acceptance']['status'] = $acceptance;
        }

        return $result;
    }

    /**
     * Returns formatted product's data
     *
     * @param   array   $data
     * @return  array
     */
    protected function prepareProductData(array $data)
    {
        // Handle product category
        if (!empty($data['mirakl_category_id'])) {
            $data[MciHelper::ATTRIBUTE_CATEGORY] = (string) $data['mirakl_category_id'];
        } elseif (!empty($data['category_paths'])) {
            $data[MciHelper::ATTRIBUTE_CATEGORY] = (string) $this->categoryHelper->getCategoryIdFromPaths($data['category_paths']);
        }

        // Handle variant group code
        if (!empty($data['parent_sku'])) {
            if (empty($data['parent_variant_group_code'])) {
                $data[McmHelper::CSV_MIRAKL_VARIANT_GROUP_CODE] = $data['parent_sku'];
            } else {
                $data[McmHelper::CSV_MIRAKL_VARIANT_GROUP_CODE] = $data['parent_variant_group_code'];
            }
        }

        // Handle product images
        if (!empty($data['images'])) {
            $imagesAttributes = $this->mciHelper->getImagesAttributes();
            $i = 0;
            foreach (array_keys($imagesAttributes) as $code) {
                if (isset($data['images'][$i])) {
                    $data[$code] = $data['images'][$i];
                } else {
                    break;
                }
                $i++;
            }
        }

        // Ensure that internal fields are removed
        unset(
            $data['images'],
            $data['mirakl_category_id'],
            $data['category_ids'],
            $data['category_paths'],
            $data[McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID],
            $data[McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER],
            $data['parent_id'],
            $data['parent_sku'],
            $data['parent_variant_group_code']
        );

        foreach ($data as $key => $value) {
            if (is_string($value) && $this->productHelper->isAttributeMultiSelect($key)) {
                $data[$key] = explode(',', $value);
            }
        }

        return $data;
    }

    /**
     * Prepares product data for export
     *
     * @param   int     $productId
     * @param   string  $acceptance
     * @return  array
     */
    public function prepareProductFromId($productId, $acceptance = ProductAcceptance::STATUS_ACCEPTED)
    {
        $product = $this->productHelper->getSingleProductData($productId);

        return $this->prepare($product, $acceptance);
    }

    /**
     * @param   int $productId
     * @return  int|false
     */
    public function rejectProduct($productId)
    {
        return $this->exportProduct($productId, ProductAcceptance::STATUS_REJECTED);
    }

    /**
     * @param   array   $productIds
     * @return  int|false
     */
    public function rejectProducts(array $productIds)
    {
        return $this->exportProducts($productIds, ProductAcceptance::STATUS_REJECTED);
    }
}
