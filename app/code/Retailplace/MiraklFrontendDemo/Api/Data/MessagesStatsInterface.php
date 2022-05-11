<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Api\Data;

/**
 * MessagesStats Interface
 */
interface MessagesStatsInterface
{

    const ID = 'id';

    const CUSTOMER_ID = 'customer_id';

    const THREAD_ID = 'thread_id';

    const TOTAL_COUNT = 'total_count';

    const UPDATED_AT = 'updated_at';

    const ENTITY_ID = 'entity_id';

    const TYPE = 'type';

    /**
     * Set Id
     *
     * @param int $id
     * @return MessagesStatsInterface
     */
    public function setId($id);

    /**
     * Get Id
     *
     * @return int
     */
    public function getId();

    /**
     * Set CustomerId
     *
     * @param int $customerId
     * @return MessagesStatsInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get CustomerId
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set ThreadId
     *
     * @param string $threadId
     * @return MessagesStatsInterface
     */
    public function setThreadId($threadId);

    /**
     * Get ThreadId
     *
     * @return string
     */
    public function getThreadId();

    /**
     * Set EntityId
     *
     * @param string $entityId
     * @return MessagesStatsInterface
     */
    public function setEntityId($entityId);

    /**
     * Get EntityId
     *
     * @return string
     */
    public function getEntityId();

    /**
     * Set Type
     *
     * @param string $type
     * @return MessagesInterface
     */
    public function setType($type);

    /**
     * Get Type
     *
     * @return string
     */
    public function getType();


    /**
     * Set TotalCount
     *
     * @param int $totalCount
     * @return MessagesStatsInterface
     */
    public function setTotalCount($totalCount);

    /**
     * Get TotalCount
     *
     * @return int
     */
    public function getTotalCount();

    /**
     * Set UpdatedAt
     *
     * @param string $updatedAt
     * @return MessagesStatsInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get UpdatedAt
     *
     * @return string
     */
    public function getUpdatedAt();

}

