<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesStatsInterface;

/**
 * MessagesStats Class
 */
class MessagesStats extends AbstractModel implements IdentityInterface, MessagesStatsInterface
{
    const NOROUTE_ENTITY_ID = 'no-route';

    const CACHE_TAG = 'retailplace_miraklfrontenddemo_messagesstats';

    protected $_cacheTag = 'retailplace_miraklfrontenddemo_messagesstats';

    protected $_eventPrefix = 'retailplace_miraklfrontenddemo_messagesstats';

    /**
     * set resource model
     */
    public function _construct()
    {
        $this->_init(ResourceModel\MessagesStats::class);
    }

    /**
     * Load No-Route Indexer.
     *
     * @return $this
     */
    public function noRouteReasons()
    {
        return $this->load(self::NOROUTE_ENTITY_ID, $this->getIdFieldName());
    }

    /**
     * Get identities.
     *
     * @return []
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Set Id
     *
     * @param int $id
     * @return MessagesStatsInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get Id
     *
     * @return int
     */
    public function getId()
    {
        return parent::getData(self::ID);
    }

    /**
     * Set CustomerId
     *
     * @param int $customerId
     * @return MessagesStatsInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get CustomerId
     *
     * @return int
     */
    public function getCustomerId()
    {
        return parent::getData(self::CUSTOMER_ID);
    }

    /**
     * Set ThreadId
     *
     * @param string $threadId
     * @return MessagesStatsInterface
     */
    public function setThreadId($threadId)
    {
        return $this->setData(self::THREAD_ID, $threadId);
    }

    /**
     * Get ThreadId
     *
     * @return string
     */
    public function getThreadId()
    {
        return parent::getData(self::THREAD_ID);
    }

    /**
     * Set EntityId
     *
     * @param string $entityId
     * @return MessagesStatsInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get EntityId
     *
     * @return string
     */
    public function getEntityId()
    {
        return parent::getData(self::ENTITY_ID);
    }

    /**
     * Set Type
     *
     * @param string $type
     * @return MessagesStatsInterface
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType()
    {
        return parent::getData(self::TYPE);
    }

    /**
     * Set TotalCount
     *
     * @param int $totalCount
     * @return MessagesStatsInterface
     */
    public function setTotalCount($totalCount)
    {
        return $this->setData(self::TOTAL_COUNT, $totalCount);
    }

    /**
     * Get TotalCount
     *
     * @return int
     */
    public function getTotalCount()
    {
        return parent::getData(self::TOTAL_COUNT);
    }

    /**
     * Set UpdatedAt
     *
     * @param string $updatedAt
     * @return MessagesStatsInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get UpdatedAt
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return parent::getData(self::UPDATED_AT);
    }
}
