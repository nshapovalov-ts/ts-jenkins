<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Model\CollectionProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\JoinProcessor as CollectionJoinProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\JoinProcessor\CustomJoinInterface;
use InvalidArgumentException;

class JoinProcessor extends CollectionJoinProcessor
{

    /**
     * @var CustomJoinInterface[]
     */
    private $joins;

    /**
     * @var array
     */
    private $fieldMapping;

    /**
     * @var array
     */
    private $appliedFields = [];


    /**
     * @param CustomJoinInterface[] $customJoins
     * @param array $fieldMapping
     */
    public function __construct(
        array $customJoins = [],
        array $fieldMapping = []
    ) {
        parent::__construct($customJoins, $fieldMapping);
        $this->joins = $customJoins;
        $this->fieldMapping = $fieldMapping;
    }


    /**
     * Reset Applied Fields
     */
    public function resetAppliedFields()
    {
        $this->appliedFields = [];
    }

    /**
     * Apply Search Criteria Filters to collection only if we need this
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param AbstractDb $collection
     * @return void
     */
    public function process(SearchCriteriaInterface $searchCriteria, AbstractDb $collection)
    {
        $this->resetAppliedFields();

        if ($searchCriteria->getFilterGroups()) {
            //Process filters
            foreach ($searchCriteria->getFilterGroups() as $group) {
                foreach ($group->getFilters() as $filter) {
                    if (!isset($this->appliedFields[$filter->getField()])) {
                        $this->applyCustomJoin($filter->getField(), $collection);
                        $this->appliedFields[$filter->getField()] = true;
                    }
                }
            }
        }

        if ($searchCriteria->getSortOrders()) {
            // Process Sortings
            foreach ($searchCriteria->getSortOrders() as $order) {
                if (!isset($this->appliedFields[$order->getField()])) {
                    $this->applyCustomJoin($order->getField(), $collection);
                    $this->appliedFields[$order->getField()] = true;
                }
            }
        }
    }

    /**
     * Apply join to collection
     *
     * @param string $field
     * @param AbstractDb $collection
     * @return void
     */
    private function applyCustomJoin(string $field, AbstractDb $collection)
    {
        $field = $this->getFieldMapping($field);
        $customJoin = $this->getCustomJoin($field);

        if ($customJoin) {
            $customJoin->apply($collection);
        }
    }

    /**
     * Return custom filters for field if exists
     *
     * @param string $field
     * @return CustomJoinInterface|null
     * @throws InvalidArgumentException
     */
    private function getCustomJoin(string $field): ?CustomJoinInterface
    {
        $filter = null;
        if (isset($this->joins[$field])) {
            $filter = $this->joins[$field];
            if (!($this->joins[$field] instanceof CustomJoinInterface)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Custom join for %s must implement %s interface.',
                        $field,
                        CustomJoinInterface::class
                    )
                );
            }
        }
        return $filter;
    }

    /**
     * Return mapped field name
     *
     * @param string $field
     * @return string
     */
    private function getFieldMapping(string $field): string
    {
        return $this->fieldMapping[$field] ?? $field;
    }
}
