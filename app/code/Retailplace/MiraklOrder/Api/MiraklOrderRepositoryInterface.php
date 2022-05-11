<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Api;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\MiraklOrder\Api\Data\MiraklOrderInterface;
use Exception;

/**
 * Interface MiraklOrderRepositoryInterface
 */
interface MiraklOrderRepositoryInterface
{
    /**
     * Get Mirakl Order by id
     *
     * @param int $entityId
     * @return MiraklOrderInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): MiraklOrderInterface;

    /**
     * Get Mirakl Order by Mirakl Order ID
     *
     * @param string $miraklOrderId
     * @return MiraklOrderInterface
     * @throws NoSuchEntityException
     */
    public function getByMiraklOrderId(string $miraklOrderId): MiraklOrderInterface;

    /**
     * Save Mirakl Order
     *
     * @param MiraklOrderInterface $miraklOrder
     * @return MiraklOrderInterface
     * @throws AlreadyExistsException
     */
    public function save(MiraklOrderInterface $miraklOrder): MiraklOrderInterface;

    /**
     * Delete Mirakl Order.
     *
     * @param MiraklOrderInterface $miraklOrder
     * @throws Exception
     */
    public function delete(MiraklOrderInterface $miraklOrder);

    /**
     * Delete Mirakl Order by Id
     *
     * @param int $miraklOrderId
     * @throws Exception
     */
    public function deleteById(int $miraklOrderId);

    /**
     * Get Mirakl Order list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface;
}
