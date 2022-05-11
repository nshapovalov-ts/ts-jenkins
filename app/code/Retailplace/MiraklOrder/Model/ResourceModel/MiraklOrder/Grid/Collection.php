<?php

namespace Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder\Grid;

use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder\Collection as MiraklOrderCollection;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class Collection extends MiraklOrderCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * Init Collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Document::class, MiraklOrder::class);
    }

    /**
     * Set items list.
     *
     * @param array|null $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Join tables
     *
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
    }
}
