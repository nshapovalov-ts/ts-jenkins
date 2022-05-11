<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Api;

use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesResponseInterface;
use Retailplace\MiraklFrontendDemo\Model\MessagesStats;
use Retailplace\MiraklFrontendDemo\Model\ResourceModel\MessagesStats\Collection;

/**
 * MessagesStatsRepository Interface
 */
interface MessagesStatsRepositoryInterface
{

    /**
     * get by id
     *
     * @param int $id
     * @return MessagesStats
     */
    public function getById($id): MessagesStats;

    /**
     * get by id
     *
     * @param MessagesStats $subject
     * @return MessagesStats
     */
    public function save(MessagesStats $subject);

    /**
     * get list
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResults
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * Get Collection
     *
     * @return Collection
     */
    public function getCollection(): Collection;

    /**
     * delete
     *
     * @param MessagesStats $subject
     * @return boolean
     */
    public function delete(MessagesStats $subject);

    /**
     * Delete by id
     *
     * @param int $id
     * @return boolean
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool;

    /**
     * @param $customerId
     * @param $entityId
     * @param $type
     * @return mixed|void
     */
    public function getThreadByCustomerIdAndEntityId($customerId, $entityId, $type);

    /**
     * @param $threads
     * @return mixed|void
     * @throws CouldNotSaveException
     */
    public function updateThreads($threads);

    /**
     * @param int $customerId
     * @return MessagesResponseInterface
     */
    public function getNewMessagesCount(int $customerId): MessagesResponseInterface;

    /**
     * @param string $customerId
     * @param $model
     * @return void
     */
    public function getAllMySentMessages(string $customerId, $model);

    /**
     * @return array
     * @throws Exception
     */
    public function getThreadInfo(): array;

}

