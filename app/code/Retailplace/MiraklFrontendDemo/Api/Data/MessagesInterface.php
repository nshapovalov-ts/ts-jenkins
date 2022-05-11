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
 * Messages Interface
 */
interface MessagesInterface
{

    const ID = 'id';

    const MESSAGE_ID = 'message_id';

    const THREAD_ID = 'thread_id';

    const IS_ATTACHMENT = 'is_attachment';

    const TYPE = 'type';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * Set Id
     *
     * @param int $id
     * @return MessagesInterface
     */
    public function setId($id);

    /**
     * Get Id
     *
     * @return int
     */
    public function getId();

    /**
     * Set MessageId
     *
     * @param string $messageId
     * @return MessagesInterface
     */
    public function setMessageId($messageId);

    /**
     * Get MessageId
     *
     * @return string
     */
    public function getMessageId();

    /**
     * Set ThreadId
     *
     * @param string $threadId
     * @return MessagesInterface
     */
    public function setThreadId($threadId);

    /**
     * Get ThreadId
     *
     * @return string
     */
    public function getThreadId();

    /**
     * Set IsAttachment
     *
     * @param int $isAttachment
     * @return MessagesInterface
     */
    public function setIsAttachment($isAttachment);

    /**
     * Get IsAttachment
     *
     * @return int
     */
    public function getIsAttachment();

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
     * Set CreatedAt
     *
     * @param string $createdAt
     * @return MessagesInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get CreatedAt
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set UpdatedAt
     *
     * @param string $updatedAt
     * @return MessagesInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get UpdatedAt
     *
     * @return string
     */
    public function getUpdatedAt();

}

