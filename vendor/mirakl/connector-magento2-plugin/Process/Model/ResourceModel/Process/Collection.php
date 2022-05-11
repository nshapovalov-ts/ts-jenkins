<?php
namespace Mirakl\Process\Model\ResourceModel\Process;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mirakl\Process\Model\Process;

/**
 * @method Process getFirstItem()
 */
class Collection extends AbstractCollection
{
    /**
     * Set resource model
     */
    protected function _construct()
    {
        $this->_init(Process::class, \Mirakl\Process\Model\ResourceModel\Process::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        /** @var Process $item */
        foreach ($this->_items as $item) {
            $this->getResource()->unserializeFields($item);
        }

        return parent::_afterLoad();
    }

    /**
     * Adds API Type filter to current collection
     *
     * @return  $this
     */
    public function addApiTypeFilter()
    {
        return $this->addFieldToFilter('type', 'API');
    }

    /**
     * Adds completed status filter to current collection
     *
     * @return  $this
     */
    public function addCompletedFilter()
    {
        return $this->addStatusFilter(Process::STATUS_COMPLETED);
    }

    /**
     * Excludes processes that have the same hash as the given ones
     *
     * @param   string|array    $hash
     * @return  $this
     */
    public function addExcludeHashFilter($hash)
    {
        if (empty($hash)) {
            return $this;
        }

        if (!is_array($hash)) {
            $hash = [$hash];
        }

        return $this->addFieldToFilter('hash', ['nin' => $hash]);
    }

    /**
     * Adds idle status filter to current collection
     *
     * @return  $this
     */
    public function addIdleFilter()
    {
        return $this->addStatusFilter(Process::STATUS_IDLE);
    }

    /**
     * Adds pending status filter to current collection
     *
     * @return  $this
     */
    public function addPendingFilter()
    {
        return $this->addStatusFilter(Process::STATUS_PENDING);
    }

    /**
     * Adds processing status filter to current collection
     *
     * @return  $this
     */
    public function addProcessingFilter()
    {
        return $this->addStatusFilter(Process::STATUS_PROCESSING);
    }

    /**
     * Adds processing status filter to current collection for mirakl_status field
     *
     * @return  $this
     */
    public function addMiraklProcessingFilter()
    {
        return $this->addFieldToFilter('mirakl_status', Process::STATUS_PROCESSING);
    }

    /**
     * Adds pending status filter to current collection for mirakl_status field
     *
     * @return  $this
     */
    public function addMiraklPendingFilter()
    {
        return $this->addFieldToFilter('mirakl_status', Process::STATUS_PENDING);
    }

    /**
     * @param   string  $status
     * @return  $this
     */
    public function addStatusFilter($status)
    {
        return $this->addFieldToFilter('status', $status);
    }
}
