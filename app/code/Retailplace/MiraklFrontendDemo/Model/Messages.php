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
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesInterface;

/**
 * Messages Class
 */
class Messages extends AbstractModel implements IdentityInterface, MessagesInterface
{

    const NOROUTE_ENTITY_ID = 'no-route';

    const CACHE_TAG = 'retailplace_miraklfrontenddemo_messages';

    protected $_cacheTag = 'retailplace_miraklfrontenddemo_messages';

    protected $_eventPrefix = 'retailplace_miraklfrontenddemo_messages';

    /**
     * set resource model
     */
    public function _construct()
    {
        $this->_init(ResourceModel\Messages::class);
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
     * @return MessagesInterface
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
     * Set MessageId
     *
     * @param string $messageId
     * @return MessagesInterface
     */
    public function setMessageId($messageId)
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    /**
     * Get MessageId
     *
     * @return string
     */
    public function getMessageId()
    {
        return parent::getData(self::MESSAGE_ID);
    }

    /**
     * Set ThreadId
     *
     * @param string $threadId
     * @return MessagesInterface
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
     * Set IsAttachment
     *
     * @param int $isAttachment
     * @return MessagesInterface
     */
    public function setIsAttachment($isAttachment)
    {
        return $this->setData(self::IS_ATTACHMENT, $isAttachment);
    }

    /**
     * Get IsAttachment
     *
     * @return int
     */
    public function getIsAttachment()
    {
        return parent::getData(self::IS_ATTACHMENT);
    }

    /**
     * Set Type
     *
     * @param string $type
     * @return MessagesInterface
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
     * Set CreatedAt
     *
     * @param string $createdAt
     * @return MessagesInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get CreatedAt
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return parent::getData(self::CREATED_AT);
    }

    /**
     * Set UpdatedAt
     *
     * @param string $updatedAt
     * @return MessagesInterface
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
