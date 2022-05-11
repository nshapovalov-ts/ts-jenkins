<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Retailplace\Minorderamount\Override\Magento\Catalog\Model\ResourceModel\Layer\Filter;

/**
 * Catalog Layer Decimal attribute Filter Resource Model
 *
 * @api
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Decimal extends \Magento\Catalog\Model\ResourceModel\Layer\Filter\Decimal
{
    /**
     * Apply attribute filter to product collection
     *
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @param float $range
     * @param int $index
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Catalog\Model\ResourceModel\Layer\Filter\Decimal
     */
    public function applyFilterToCollection(\Magento\Catalog\Model\Layer\Filter\FilterInterface $filter, $range, $index)
    {
        $collection = $filter->getLayer()->getProductCollection();
        $attribute = $filter->getAttributeModel();
        $connection = $this->getConnection();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $collection->getStoreId()),
        ];

        $collection->getSelect()->join(
            [$tableAlias => $this->getMainTable()],
            implode(' AND ', $conditions),
            []
        );
        $customCondition = $range * ($index - 1);

        if($filter->getAttributeModel()->getAttributeCode() == "min_order_amount"){
            if($range == 100 && $index == 1){
                $customCondition = 1;
            }
        }
        $collection->getSelect()->where(
            "{$tableAlias}.value >= ?",
            $customCondition
        )->where(
            "{$tableAlias}.value < ?",
            $range * $index
        );

        return $this;
    }

    /**
     * Retrieve clean select with joined index table
     * Joined table has index
     *
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\DB\Select
     */
    protected function _getSelect($filter,$checkNoMinimumOrderAmount = false)
    {
        $collection = $filter->getLayer()->getProductCollection();

        // clone select from collection with filters
        $select = clone $collection->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

        $attributeId = $filter->getAttributeModel()->getId();
        $storeId = $collection->getStoreId();
        $customCondition = "";
        if($filter->getAttributeModel()->getAttributeCode() == "min_order_amount"){
            if($checkNoMinimumOrderAmount){
                $customCondition =  ' AND ' . $this->getConnection()->quoteInto(
                        'decimal_index.value = ?',
                        0
                    );
            }else{
                $customCondition =  ' AND ' .$this->getConnection()->quoteInto(
                        'decimal_index.value > ?',
                        0
                    );
            }
        }

        $select->join(
            ['decimal_index' => $this->getMainTable()],
            'e.entity_id = decimal_index.entity_id' . ' AND ' . $this->getConnection()->quoteInto(
                'decimal_index.attribute_id = ?',
                $attributeId
            ) . ' AND ' . $this->getConnection()->quoteInto(
                'decimal_index.store_id = ?',
                $storeId
            ). $customCondition,
            []
        );

        return $select;
    }

    /**
     * Retrieve array with products counts per range
     *
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @param int $range
     * @return array
     */
    public function getCount(\Magento\Catalog\Model\Layer\Filter\FilterInterface $filter, $range)
    {
        if($filter->getAttributeModel()->getAttributeCode() == "min_order_amount"){
            $select =  $this->_getSelect($filter,true);
            $connection = $this->getConnection();

            $countExpr = new \Zend_Db_Expr("COUNT(*)");
            $rangeExpr = new \Zend_Db_Expr("FLOOR(decimal_index.value / {$range}) + 1");

            $select->columns(['decimal_range' => $rangeExpr, 'count' => $countExpr]);
            $select->group($rangeExpr);

            $firstresult = $connection->fetchPairs($select);

            $select =  $this->_getSelect($filter);
            $connection = $this->getConnection();

            $countExpr = new \Zend_Db_Expr("COUNT(*)");
            $rangeExpr = new \Zend_Db_Expr("FLOOR(decimal_index.value / {$range}) + 1");

            $select->columns(['decimal_range' => $rangeExpr, 'count' => $countExpr]);
            $select->group($rangeExpr);
            if ($firstresult && isset($firstresult[1])) {
                $result = $connection->fetchPairs($select);
                $result[0] = $firstresult[1];
                ksort($result);
            } else {
                $result = $connection->fetchPairs($select);
            }

        }else{
            $select =  $this->_getSelect($filter);
            $connection = $this->getConnection();

            $countExpr = new \Zend_Db_Expr("COUNT(*)");
            $rangeExpr = new \Zend_Db_Expr("FLOOR(decimal_index.value / {$range}) + 1");

            $select->columns(['decimal_range' => $rangeExpr, 'count' => $countExpr]);
            $select->group($rangeExpr);
            $result = $connection->fetchPairs($select);
        }
        return $result;
    }
}
