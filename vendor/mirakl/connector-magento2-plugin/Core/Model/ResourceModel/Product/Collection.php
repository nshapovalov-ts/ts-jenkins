<?php
namespace Mirakl\Core\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Framework\Exception\LocalizedException;
use Mirakl\Mcm\Helper\Data as McmHelper;


/**
 * /!\ This is not an override of the default Magento product collection but just an extension
 * in order to manipulate collection items as arrays instead of product objects for better performances.
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * @var bool
     */
    protected $_isEnterprise;

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_isEnterprise = \Mirakl\Core\Helper\Data::isEnterprise();
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  $this
     */
    public function addAttributeOptionValue(EavAttribute $attribute)
    {
        if (!$this->isAttributeUsingOptions($attribute)) {
            return $this->addAttributeToSelect($attribute->getAttributeCode());
        }

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        $attributeCode = $attribute->getAttributeCode();
        $entityCol = $this->_isEnterprise ? 'row_id' : 'entity_id';

        $valueTable1 = $attributeCode . '_t1';
        $valueTable2 = $attributeCode . '_t2';
        $this->getSelect()
            ->joinLeft(
                [$valueTable1 => $attribute->getBackend()->getTable()],
                "e.{$entityCol} = {$valueTable1}.{$entityCol}"
                . " AND {$valueTable1}.attribute_id = {$attribute->getId()}"
                . " AND {$valueTable1}.store_id = 0",
                []
            )
            ->joinLeft(
                [$valueTable2 => $attribute->getBackend()->getTable()],
                "e.{$entityCol} = {$valueTable2}.{$entityCol}"
                . " AND {$valueTable2}.attribute_id = {$attribute->getId()}"
                . " AND {$valueTable2}.store_id = {$storeId}",
                []
            );

        $valueExpr = $this->_conn->getCheckSql(
            "{$valueTable2}.value_id > 0",
            "{$valueTable2}.value",
            "{$valueTable1}.value"
        );

        $optionTable1   = $attributeCode . '_option_value_t1';
        $optionTable2   = $attributeCode . '_option_value_t2';
        $tableJoinCond1 = "{$optionTable1}.option_id = {$valueExpr} AND {$optionTable1}.store_id = 0";
        $tableJoinCond2 = "{$optionTable2}.option_id = {$valueExpr} AND {$optionTable2}.store_id = {$storeId}";
        $valueExpr      = $this->_conn->getCheckSql("{$optionTable2}.value_id IS NULL",
            "{$optionTable1}.value",
            "{$optionTable2}.value"
        );

        $this->getSelect()
            ->joinLeft(
                [$optionTable1 => $this->getTable('eav_attribute_option_value')],
                $tableJoinCond1,
                []
            )
            ->joinLeft(
                [$optionTable2 => $this->getTable('eav_attribute_option_value')],
                $tableJoinCond2,
                [$attributeCode => $valueExpr]
            );

        return $this;
    }

    /**
     * Add category ids to loaded items
     *
     * @param   bool    $fallbackToParent
     * @return  $this
     */
    public function addCategoryIds($fallbackToParent = true)
    {
        if ($this->getFlag('category_ids_added')) {
            return $this;
        }

        $productIds = array_keys($this->_items);
        if (empty($productIds)) {
            return $this;
        }

        $productCategoryIds = $this->getProductCategoryIds($productIds);

        $productsWithCategories = [];
        foreach ($productCategoryIds as $productId => $categoryIds) {
            $productsWithCategories[$productId] = true;
            $this->_items[$productId]['category_ids'] = $categoryIds;
        }

        if ($fallbackToParent) {
            // Search for categories associated to parent product if possible
            $productsWithoutCategories = array_diff_key($this->_items, $productsWithCategories);
            $parentProductIds = $this->getParentProductIds(array_keys($productsWithoutCategories));
            if (!empty($parentProductIds)) {
                $parentIds = [];
                foreach ($parentProductIds as $ids) {
                    $parentIds = array_merge($parentIds, $ids);
                }
                $parentIds = array_unique($parentIds);
                $parentProductCategoryIds = $this->getProductCategoryIds($parentIds);
                foreach ($parentProductIds as $productId => $parentIds) {
                    foreach ($parentIds as $parentId) {
                        if (isset($parentProductCategoryIds[$parentId])) {
                            $this->_items[$productId]['category_ids'] = $parentProductCategoryIds[$parentId];
                            continue 2; // skip this product as soon as we have found some categories for it
                        }
                    }
                }
            }
        }

        $this->setFlag('category_ids_added', true);

        return $this;
    }

    /**
     * Add category paths to loaded items
     *
     * @return  $this
     */
    public function addCategoryPaths()
    {
        if ($this->getFlag('category_paths_added') || empty($this->_items)) {
            return $this;
        }

        // Category ids are required
        $this->addCategoryIds();

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        /** @var EavAttribute $attribute */
        $attribute = $this->_eavConfig->getAttribute('catalog_category', 'name');
        $entityCol = $this->_isEnterprise ? 'row_id' : 'entity_id';

        $colsExprSql = [
            'category_id' => 'categories.entity_id',
            'path' => 'categories.path',
            'name' => $this->_conn->getIfNullSql('category_name_t2.value', 'category_name_t1.value')
        ];
        $select = $this->_conn
            ->select()
            ->from(['categories' => $this->getTable('catalog_category_entity')], $colsExprSql)
            ->joinLeft(
                ['category_name_t1' => $attribute->getBackend()->getTable()],
                "categories.$entityCol = category_name_t1.$entityCol"
                . " AND category_name_t1.attribute_id = {$attribute->getId()}"
                . " AND category_name_t1.store_id = 0",
                []
            )
            ->joinLeft(
                ['category_name_t2' => $attribute->getBackend()->getTable()],
                "categories.$entityCol = category_name_t2.$entityCol"
                . " AND category_name_t2.attribute_id = {$attribute->getId()}"
                . " AND category_name_t2.store_id = {$storeId}",
                []
            );

        $categories = $this->_conn->fetchAssoc($select);

        $getCategoryPath = function ($categoryId) use ($categories) {
            $pathNames = [];
            if (isset($categories[$categoryId])) {
                $pathCategoryIds = explode('/', $categories[$categoryId]['path']);
                foreach ($pathCategoryIds as $pathCategoryId) {
                    if ($pathCategoryId > 1 && isset($categories[$pathCategoryId])) {
                        $pathNames[] = $categories[$pathCategoryId]['name'];
                    }
                }
            }

            return $pathNames;
        };

        foreach ($this->_items as $productId => $data) {
            $this->_items[$productId]['category_paths'] = [];
            if (isset($data['category_ids'])) {
                foreach ($data['category_ids'] as $categoryId) {
                    $this->_items[$productId]['category_paths'][$categoryId] = $getCategoryPath($categoryId);
                }
            }
        }

        $this->setFlag('category_paths_added', true);

        return $this;
    }

    /**
     * Add image URL to loaded items
     *
     * @param   string  $key
     * @return  $this
     */
    public function addMediaGalleryAttribute($key = 'images')
    {
        $productIds = array_keys($this->_items);

        if (empty($productIds) || $this->getFlag('images_url_added')) {
            return $this;
        }

        // Retrieve products images
        $productImages = $this->getProductImages($productIds);

        // Retrieve parent product images for products without image associated
        $productsWithoutImages = array_diff_key($this->_items, $productImages);
        if (!empty($productsWithoutImages)) {
            $parentProductIds = $this->getParentProductIds(array_keys($productsWithoutImages));
            if (!empty($parentProductIds)) {
                $parentIds = [];
                foreach ($parentProductIds as $ids) {
                    $parentIds = array_merge($parentIds, $ids);
                }
                $parentIds = array_unique($parentIds);
                $parentProductImages = $this->getProductImages($parentIds);
                foreach ($parentProductIds as $productId => $parentIds) {
                    foreach ($parentIds as $parentId) {
                        if (isset($parentProductImages[$parentId])) {
                            $productImages[$productId] = $parentProductImages[$parentId];
                            continue 2; // skip this product as soon as we have found some images for it
                        }
                    }
                }
            }
        }

        foreach ($productImages as $productId => $images) {
            if (!isset($this->_items[$productId][$key])) {
                $this->_items[$productId][$key] = [];
            }
            foreach ($images as $image) {
                $file = !empty($image['file']) ? $image['file'] : $image['file_default'];
                $this->_items[$productId][$key][] = $this->getMediaUrl($file);
            }
        }

        $this->setFlag('images_url_added', true);

        return $this;
    }

    /**
     * @param   string  $file
     * @return  string
     */
    protected function getMediaUrl($file)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        $file = ltrim(str_replace('\\', '/', $file), '/');

        return $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/' . $file;
    }

    /**
     * Returns parent ids of specified product ids
     *
     * @param   array   $productIds
     * @return  array
     */
    protected function getParentProductIds(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $select = $this->_conn->select()
            ->from($this->getTable('catalog_product_super_link'), ['product_id', 'parent_id'])
            ->where('product_id IN (?)', $productIds);

        $parentIds = array_fill_keys($productIds, []);
        foreach ($this->_conn->fetchAll($select) as $row) {
            $productId = $row['product_id'];
            $parentIds[$productId][] = (int) $row['parent_id'];
        }

        return $parentIds;
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getProductCategoryIds(array $productIds)
    {
        $select = $this->_conn
            ->select()
            ->from($this->_productCategoryTable, ['product_id', 'category_id'])
            ->where('product_id IN (?)', $productIds);

        $categoryIds = [];

        $stmt = $this->_conn->query($select);
        while ($row = $stmt->fetch()) {
            $productId = $row['product_id'];
            if (!isset($categoryIds[$productId])) {
                $categoryIds[$productId] = [];
            }
            if (null !== $row['category_id']) {
                $categoryIds[$productId][] = (int) $row['category_id'];
            }
        }
        unset($stmt);

        return $categoryIds;
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getProductImages(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = $this->_storeManager->getDefaultStoreView()->getId();
        }

        $attribute = $this->getAttribute('image');
        $attributeId = $attribute ? $attribute->getId() : null;

        $entityCol = $this->_isEnterprise ? 'row_id' : 'entity_id';
        $select = $this->_conn->select()
            ->from(['cpe' => $this->getTable('catalog_product_entity')], 'entity_id')
            ->joinLeft(
                ['mgv' => $this->getTable('catalog_product_entity_media_gallery_value')],
                "(mgv.$entityCol = cpe.$entityCol AND mgv.store_id = $storeId)",
                ['label', 'position']
            )
            ->joinLeft(
                ['mg1' => $this->getTable('catalog_product_entity_media_gallery')],
                'mg1.value_id = mgv.value_id',
                ['file' => 'value']
            )
            ->joinLeft(
                ['mgvbi' => $this->getTable('catalog_product_entity_varchar')],
                "(mgvbi.$entityCol = cpe.$entityCol AND mg1.value = mgvbi.value AND " .
                "mgvbi.store_id = $storeId AND mgvbi.attribute_id = $attributeId)",
                ['base_image' => 'value_id']
            )
            ->joinLeft(
                ['mgdv' => $this->getTable('catalog_product_entity_media_gallery_value')],
                "(mgdv.$entityCol = cpe.$entityCol AND mgdv.store_id = 0)",
                ['label_default' => 'label', 'position_default' => 'position']
            )
            ->joinLeft(
                ['mg2' => $this->getTable('catalog_product_entity_media_gallery')],
                'mg2.value_id = mgdv.value_id',
                ['file_default' => 'value']
            )
            ->joinLeft(
                ['mgdvbi' => $this->getTable('catalog_product_entity_varchar')],
                "(mgdvbi.$entityCol = cpe.$entityCol AND mg2.value = mgdvbi.value AND " .
                "mgdvbi.store_id = $storeId AND mgdvbi.attribute_id = $attributeId)",
                ['base_image_default' => 'value_id']
            )
            ->where('cpe.entity_id IN (?)', $productIds)
            ->order(['base_image DESC', 'base_image_default DESC', 'position ASC', 'position_default ASC', 'file ASC', 'file_default ASC']);

        $images = [];
        $stmt = $this->_conn->query($select);
        while ($row = $stmt->fetch()) {
            if (empty($row['file']) && empty($row['file_default'])) {
                continue;
            }
            $productId = $row['entity_id'];
            if (!isset($images[$productId])) {
                $images[$productId] = [];
            }
            $images[$productId][] = $row;
        }
        unset($stmt);

        return $images;
    }

    /**
     * Checks if specified attribute is using options or not
     *
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeUsingOptions(EavAttribute $attribute)
    {
        $model = $attribute->getSource();
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
            ($backend == 'int' && $model instanceof \Magento\Eav\Model\Entity\Attribute\Source\Table) ||
            ($backend == 'varchar' && $attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * {@inheritdoc}
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_renderFilters();
        $this->_renderOrders();

        $this->_loadEntities($printQuery, $logQuery);
        $this->_loadAttributes($printQuery, $logQuery);

        $this->_setIsLoaded();

        return $this;
    }

    /**
     * @param   array   $attribute
     * @return  $this
     */
    public function overrideByParentData($attribute)
    {
        if ($this->getFlag('parent_data_overriden') || empty($this->_items)) {
            return $this;
        }

        $productIds = array_keys($this->_items);

        /** @var Collection $collection */
        $collection = $this->_entityFactory->create(self::class);

        if (count($attribute)) {
            $collection->addFieldToSelect($attribute);
        }

        $childIdsSql = new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT link.product_id SEPARATOR ',')");
        $entityCol = \Mirakl\Core\Helper\Data::isEnterprise() ? 'row_id' : 'entity_id';
        $collection->getSelect()
            ->joinLeft(
                ['link' => $this->getTable('catalog_product_super_link')],
                "link.parent_id = e.$entityCol",
                ['entity_ids' => $childIdsSql]
            )
            ->where('link.product_id IN (?)', $productIds)
            ->group('e.entity_id');

        foreach ($collection as $data) {
            $parentId = $data['entity_id'];
            unset($data['entity_id']);
            $data['parent_id'] = $parentId;

            $entityIds = explode(',', $data['entity_ids']);
            unset($data['entity_ids']);

            foreach ($entityIds as $entityId) {
                // If product have multiple parent, keep data from the first
                if (isset($this->_items[$entityId]['parent_id'])) {
                    continue;
                }

                foreach ($attribute as $alias => $code) {
                    $field = is_int($alias) ? $code : $alias;
                    $this->_items[$entityId][$field] = isset($data[$code]) ? $data[$code] : '';
                }
            }
        }

        $this->setFlag('parent_data_overriden', true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
        $this->getEntity();

        if ($this->_pageSize) {
            $this->getSelect()->limitPage($this->getCurPage(), $this->_pageSize);
        }

        $this->printLogQuery($printQuery, $logQuery);

        try {
            $query = $this->getSelect();
            $rows = $this->_fetchAll($query);
        } catch (\Exception $e) {
            $this->printLogQuery(true, true, $query);
            throw $e;
        }

        $entityIdField = $this->getEntity()->getEntityIdField();
        foreach ($rows as $row) {
            $entityId = $row[$entityIdField];
            $this->_items[$entityId] = $row;
            if (isset($this->_itemsById[$entityId])) {
                $this->_itemsById[$entityId][] = $row;
            } else {
                $this->_itemsById[$entityId] = [$row];
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _setItemAttributeValue($valueInfo)
    {
        $entityIdField = $this->getEntity()->getEntityIdField();
        $entityId      = $valueInfo[$entityIdField];
        if (!isset($this->_itemsById[$entityId])) {
            throw new LocalizedException(__('Data integrity: No header row found for attribute'));
        }

        $attributeCode = array_search($valueInfo['attribute_id'], $this->_selectAttributes);
        if (!$attributeCode) {
            $attribute = $this->_eavConfig->getAttribute(
                $this->getEntity()->getType(),
                $valueInfo['attribute_id']
            );
            $attributeCode = $attribute->getAttributeCode();
        }

        foreach ($this->_itemsById[$entityId] as &$data) {
            $data[$attributeCode] = $valueInfo['value'];
            $this->_items[$entityId][$attributeCode] = $valueInfo['value'];
        }
        unset($data);

        return $this;
    }
}