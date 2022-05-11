<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Api\Data;

interface MessagesResponseInterface
{
    const MESSAGES_COUNT = 'messages_count';

    /**
     * @param int|string $messagesCount
     * @return void
     */
    public function setNewMessagesCount($messagesCount);

    /**
     * @return string
     */
    public function getNewMessagesCount(): string;
}
