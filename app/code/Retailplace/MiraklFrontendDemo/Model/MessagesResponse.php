<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Model;

use Magento\Framework\Model\AbstractModel;
use Retailplace\MiraklFrontendDemo\Api\Data\MessagesResponseInterface;

class MessagesResponse extends AbstractModel implements MessagesResponseInterface
{

    /**
     * Set New Messages Count
     *
     * @param int|string $messagesCount
     * @return void
     */
    public function setNewMessagesCount($messagesCount)
    {
        $this->setData(self::MESSAGES_COUNT, $messagesCount);
    }

    /**
     * Get New Messages Count
     *
     * @return string
     */
    public function getNewMessagesCount(): string
    {
        return (string)$this->getData(self::MESSAGES_COUNT);
    }
}
