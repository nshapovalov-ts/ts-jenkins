<?php
namespace Mirakl\Event\Model\ResourceModel\Event;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mirakl\Event\Model\Event;

/**
 * @method Event getFirstItem()
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(Event::class, \Mirakl\Event\Model\ResourceModel\Event::class);
    }

    /**
     * Adds action filter to current collection
     *
     * @param   string  $action
     * @return  $this
     */
    public function addActionFilter($action)
    {
        return $this->addFieldToFilter('action', $action);
    }

    /**
     * Adds code filter to current collection
     *
     * @param   string  $code
     * @return  $this
     */
    public function addCodeFilter($code)
    {
        return $this->addFieldToFilter('code', $code);
    }

    /**
     * Adds processing status filter to current collection
     *
     * @return  $this
     */
    public function addProcessingFilter()
    {
        return $this->addFieldToFilter('status', Event::STATUS_PROCESSING);
    }

    /**
     * Adds sent status filter to current collection
     *
     * @return  $this
     */
    public function addSentFilter()
    {
        return $this->addFieldToFilter('status', Event::STATUS_SENT);
    }

    /**
     * Adds type filter to current collection
     *
     * @param   int $type
     * @return  $this
     */
    public function addTypeFilter($type)
    {
        return $this->addFieldToFilter('type', $type);
    }

    /**
     * Adds waiting status filter to current collection
     *
     * @return  $this
     */
    public function addWaitingFilter()
    {
        return $this->addFieldToFilter('status', Event::STATUS_WAITING);
    }
}