<?php

/**
 * Vdcstore_CategoryTree
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

namespace Vdcstore\CategoryTree\Model;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Category\Product as ProductIndex;
use Magento\Catalog\Model\Indexer\Product\Category as CategoryIndexer;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Vdcstore\CategoryTree\Helper\Data;
use Vdcstore\CategoryTree\Helper\UpdateAttributeHelper;
use Zend_Db_Expr;
use Magento\Catalog\Model\Category as CategoryAlias;

class Category extends AbstractModel
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var AdapterInterface
     */
    private $connection;
    /**
     * @var mixed
     */
    protected $menuRootId;
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;
    protected $updateAttributeHelper;

    /**
     * Category constructor.
     * @param Context $context
     * @param Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param Data $helper
     * @param UpdateAttributeHelper $updateAttributeHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CategoryFactory $categoryFactory,
        Data $helper,
        UpdateAttributeHelper $updateAttributeHelper,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context, $registry);
        $this->categoryFactory = $categoryFactory;
        $this->helper = $helper;
        $this->updateAttributeHelper = $updateAttributeHelper;
        $this->resourceConnection = $resourceConnection;
        $this->connection = $this->resourceConnection->getConnection();
        $this->menuRootId = $this->helper->getMenuRoot();
    }

    /**
     * @param $category
     * @return mixed
     */
    public function setMiraklCategoryProductsToMenuCategory($category)
    {
        $miraklCategoryId = $category->getId();
        $categoryCollection = $this->categoryFactory->create()->getCollection();
        $filterData = [];
        $filterData[] = ['attribute' => 'child_categories', ['finset' => $miraklCategoryId]];
        $filterData[] = ['attribute' => 'mapped_category', ['finset' => $miraklCategoryId]];
        $menuCategories = $categoryCollection
            ->addAttributeToFilter(
                $filterData,
                null,
                'left'
            )->addFieldToFilter('path', ['like' => "1/{$this->menuRootId}/%"]);

        $menuCategoriesAllIds = [];
        if ($menuCategories->getSize()) {
            foreach ($menuCategories as $menuCategory) {
                if ($menuCategory->getId()) {
                    if (!$menuCategory->getData('include_in_menu') || !$menuCategory->getData('exclude_from_menu')) {
                        $menuCategoriesAllIds[] = $menuCategory->getId();
                    }
                    $this->copyMiraklProductIdsToMenuCategory($miraklCategoryId, $menuCategory);
                }
            }
        }
        if ($menuCategoriesAllIds) {
            $attributes['include_in_menu'] = 1;
            $this->updateAttributeHelper->updateCategoryAttributes($menuCategoriesAllIds, $attributes);
        }
        return $category;
    }

    /**
     * @param string|array|null $childCategories
     * @param CategoryAlias $category
     */
    public function copyMiraklProductIdsToMenuCategory($childCategories, CategoryAlias $category)
    {
        if (strpos($category->getPath(), "1/{$this->menuRootId}/") !== false) {
            $catalogCategoryProductTable = $this->connection->getTableName('catalog_category_product');
            if ($childCategories) {
                $childCategories = explode(",", $childCategories);
                $menuCategoryId = $category->getId();

                $frontendCategorySelect = $this->connection->select()
                    ->from(
                        ['ccp' => $catalogCategoryProductTable],
                        ['product_id']
                    )
                    ->where('category_id = ?', $menuCategoryId);

                $miraklCategorySelect = $this->connection->select()
                    ->from(
                        ['ccp' => $catalogCategoryProductTable],
                        ['product_id']
                    )
                    ->where('category_id in (?)', $childCategories);

                $deleteFromSelect = (clone $frontendCategorySelect)
                    ->where('product_id not in (?)', $miraklCategorySelect);

                $miraklCategorySelect->columns(['category_id' => new Zend_Db_Expr($menuCategoryId), 'position'])
                    ->where('product_id not in (?)', $frontendCategorySelect);

                $insertData = $this->connection->fetchAll($miraklCategorySelect);
                if ($insertData) {
                    $this->connection->insertOnDuplicate($catalogCategoryProductTable, $insertData);
                }

                $deleteProductIds = $this->connection->fetchCol($deleteFromSelect);
                if ($deleteProductIds) {
                    $this->connection->delete(
                        $catalogCategoryProductTable,
                        [
                            'product_id IN(?)' => $deleteProductIds,
                            'category_id=?'    => $menuCategoryId
                        ]
                    );
                }
            } else {
                $whereConditions = [$this->connection->quoteInto('category_id = ?', $category->getId())];
                $this->connection->delete($this->connection->getTableName('catalog_category_product'), $whereConditions);
            }
        }
    }

    /**
     * @param array $categoryIds
     * @return array
     */
    public function getMenuCategoryFromMappedCategory(array $categoryIds): array
    {
        $categoryCollection = $this->categoryFactory->create()->getCollection();
        $filterData = [];
        foreach ($categoryIds as $categoryId) {
            $filterData[] = ['attribute' => 'child_categories', ['finset' => $categoryId]];
            $filterData[] = ['attribute' => 'mapped_category', ['finset' => $categoryId]];
        }
        $menuCategories = $categoryCollection
            ->addAttributeToFilter(
                $filterData,
                null,
                'left'
            )->addFieldToFilter('path', ['like' => "1/{$this->menuRootId}/%"]);

        if ($menuCategories->getSize()) {
            $menuCategoryIds = $menuCategories->getAllIds();
            $cloneMenuCategorys = $this->categoryFactory->create()->getCollection()
                ->addAttributeToFilter('include_in_menu', ['eq' => 0])
                ->addAttributeToFilter('exclude_from_menu', ['eq' => 0]);
            $catIds = $cloneMenuCategorys->getAllIds();
            if ($catIds) {
                $this->updateAttributeHelper->updateCategoryAttributes($catIds, ['include_in_menu' => 1]);
            }
            $categoryIds = array_unique(array_merge($categoryIds, $menuCategoryIds));
        }
        return $categoryIds;
    }
}
