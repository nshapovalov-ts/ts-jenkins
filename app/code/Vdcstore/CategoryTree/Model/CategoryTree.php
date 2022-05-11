<?php

namespace Vdcstore\CategoryTree\Model;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ObjectManager;
use Vdcstore\CategoryTree\Helper\Data;

/**
 * CategoryTree Product import model
 */
class CategoryTree extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Vdcstore\CategoryTree\Helper\UpdateAttributeHelper
     */
    protected $updateAttributeHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Data $helper
     * @param CollectionFactory $categoryCollectionFactory
     * @param \Vdcstore\CategoryTree\Helper\UpdateAttributeHelper $updateAttributeHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param TableMaintainer|null $tableMaintainer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Data $helper,
        CollectionFactory $categoryCollectionFactory,
        \Vdcstore\CategoryTree\Helper\UpdateAttributeHelper $updateAttributeHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        TableMaintainer $tableMaintainer = null,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->helper = $helper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->updateAttributeHelper = $updateAttributeHelper;
        $this->_storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->tableMaintainer = $tableMaintainer ?: ObjectManager::getInstance()->get(TableMaintainer::class);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return void
     */
    public function updateCategoryTree()
    {
        /** @var Collection $categoryCollection */
        $categoryCollection = $this->categoryCollectionFactory
            ->create()
            ->addFieldToFilter('path', ['like' => "1/{$this->helper->getMenuRoot()}/%"]);

        $this->updateCategories($categoryCollection);
    }

    /**
     * @param Collection $categoryCollection
     */
    public function updateCategories(Collection $categoryCollection)
    {
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('mirakl_shop_ids', ['notnull' => true]);
        $productIds = $productCollection->getAllIds();

        $categoriesWithActiveOffers = (clone $categoryCollection);
        $categoriesWithActiveOffers->getSelect()->joinLeft(
            ['cat_index' => $this->tableMaintainer->getMainTable($this->getStoreId())],
            'cat_index.category_id=e.entity_id',
            ['product_count' => 'COUNT(cat_index.product_id)']
        )->where('cat_index.product_id IN (?)', $productIds
        )->having('COUNT(cat_index.product_id) >= ?', $this->helper->getProductCountLimit()
        )->group('e.entity_id');

        $categoriesWithActiveOffersIds = $categoriesWithActiveOffers->getAllIds();

        $categoriesToShow = (clone $categoryCollection);
        $categoriesToShow
            ->addAttributeToFilter('include_in_menu', [
                ['neq' => 1],
                ['null' => true]
            ], 'left')
            ->addAttributeToFilter('exclude_from_menu', [
                ['neq' => 1],
                ['null' => true]
            ], 'left')
            ->addAttributeToFilter('entity_id', ['in' => $categoriesWithActiveOffersIds]);
        $catIdsToShow = $categoriesToShow->getAllIds();
        if ($catIdsToShow) {
            $this->updateAttributeHelper->updateCategoryAttributes($catIdsToShow, ['include_in_menu' => 1]);
        }

        $categoriesToHide = (clone $categoryCollection);
        $categoriesToHide
            ->addAttributeToFilter('include_in_menu', 1)
            ->addAttributeToFilter('entity_id', ['nin' => $categoriesWithActiveOffersIds]);
        $catIdsToHide = $categoriesToHide->getAllIds();

        $withoutMappedCollection = (clone $categoryCollection)
            ->addAttributeToFilter('include_in_menu', 1)
            ->addAttributeToFilter('exclude_from_menu', 1);
        $withoutMappedIds = $withoutMappedCollection->getAllIds();
        $catIdsToHide = array_unique(array_merge($catIdsToHide, $withoutMappedIds));

        if ($catIdsToHide) {
            $this->updateAttributeHelper->updateCategoryAttributes($catIdsToHide, ['include_in_menu' => 0]);
        }
    }
}
