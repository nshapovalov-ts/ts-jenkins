<?php
namespace Mirakl\Adminhtml\Ui\Component\View\Element\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\RegularFilter;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class OrderFlagFilter extends RegularFilter
{
    /**
     * {@inheritdoc}
     */
    public function apply(Collection $collection, Filter $filter)
    {
        $this->applyFlagFilter($collection, $filter);
    }

    /**
     * @param   Collection  $collection
     * @param   Filter      $filter
     */
    protected function applyFlagFilter(Collection $collection, Filter $filter)
    {
        if (!$value = $filter->getValue()) {
            return;
        }

        /** @var OrderGridCollection $collection */
        $select = clone $collection->getSelect();
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->columns('main_table.entity_id');
        $columns = [
            'count_magento' => new \Zend_Db_Expr('SUM(IF(items.mirakl_offer_id IS NULL, 1, 0))'),
            'count_mirakl'  => new \Zend_Db_Expr('SUM(IF(items.mirakl_offer_id IS NOT NULL, 1, 0))'),
        ];
        $select->join(
            ['items' => $collection->getTable('sales_order_item')],
            'main_table.entity_id = items.order_id AND items.parent_item_id IS NULL',
            $columns
        );
        $select->group('items.order_id');

        if ($value == 'marketplace') {
            // Filter only full Mirakl orders
            $select->having('count_magento = 0');
        } elseif ($value == 'mixed') {
            // Fitler only mixed orders
            $select->having('count_magento > 0 AND count_mirakl > 0');
        } else {
            // Filter only full Magento orders
            $select->having('count_mirakl = 0');
        }

        $orderIds = $collection->getConnection()->fetchCol($select);

        if (!empty($orderIds)) {
            $collection->addFieldToFilter('entity_id', $orderIds);
        }
    }
}