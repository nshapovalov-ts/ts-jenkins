<?php
namespace Mirakl\Core\Model\ResourceModel\Shipping\Zone;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * All Store Views value
     */
    const ALL_STORE_VIEWS = '0';

    /**
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Mirakl\Core\Model\Shipping\Zone::class, \Mirakl\Core\Model\ResourceModel\Shipping\Zone::class);
    }

    /**
     * Add store ids to rules data
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getFlag('add_stores_to_result') && $this->_items) {
            /** @var \Magento\Rule\Model\AbstractModel $item */
            foreach ($this->_items as $item) {
                $item->afterLoad();
            }
        }

        return $this;
    }

    /**
     * @return  $this
     */
    public function addActiveFilter()
    {
        return $this->addFieldToFilter('is_active', true);
    }

    /**
     * Limit shipping zone collection by specific store
     *
     * @param   mixed   $storeId
     * @return  $this
     */
    public function addStoreFilter($storeId)
    {
        if (!$this->getFlag('is_store_table_joined')) {
            $this->setFlag('is_store_table_joined', true);
            if ($storeId instanceof \Magento\Store\Model\Store) {
                $storeId = $storeId->getId();
            }
            $this->getSelect()->distinct()->joinInner(
                ['store' => $this->getTable('mirakl_shipping_zone_store')],
                sprintf('main_table.id = store.zone_id  AND (%s OR %s)',
                    $this->getConnection()->quoteInto('store.store_id  = ?', self::ALL_STORE_VIEWS),
                    $this->getConnection()->quoteInto('store.store_id  = ?', $storeId)
                ),
                []
            );
        }

        return $this;
    }

    /**
     * Provide support for store id filter
     *
     * @param   string  $field
     * @param   mixed   $condition
     * @return  $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'store_ids') {
            return $this->addStoreFilter($condition);
        }
    
        parent::addFieldToFilter($field, $condition);

        return $this;
    }

    /**
     * Filter collection to only active or inactive shipping zone
     *
     * @param   int $isActive
     * @return  $this
     */
    public function addIsActiveFilter($isActive = 1)
    {
        if (!$this->getFlag('is_active_filter')) {
            $this->addFieldToFilter('is_active', (int) $isActive ? 1 : 0);
            $this->setFlag('is_active_filter', true);
        }

        return $this;
    }

    /**
     * Init flag for adding shipping zone store ids to collection result
     *
     * @param   bool|null   $flag
     * @return  $this
     */
    public function addStoresToResult($flag = null)
    {
        $flag = $flag === null ? true : $flag;
        $this->setFlag('add_stores_to_result', $flag);

        return $this;
    }

    /**
     * Sets sort order
     *
     * @param   string  $direction
     * @return  $this
     */
    public function setSortOrder($direction = self::SORT_ORDER_ASC)
    {
        return $this->addOrder('sort_order', $direction);
    }
}
