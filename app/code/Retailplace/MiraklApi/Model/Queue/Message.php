<?php

/**
 * Retailplace_MiraklApi
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklApi\Model\Queue;

use Magento\Framework\DataObject;
use Retailplace\MiraklApi\Api\Data\Queue\MessageInterface;

/**
 * Message class
 */
class Message extends DataObject implements MessageInterface
{
    /**
     * Get Order ID
     *
     * @return int
     */
    public function getOrderId(): int
    {
        return (int) $this->getData(self::ORDER_ID);
    }

    /**
     * Set Order ID
     *
     * @param int $orderId
     * @return MessageInterface
     */
    public function setOrderId(int $orderId): MessageInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get Mark As Sent
     *
     * @return mixed
     */
    public function getMarkAsSent()
    {
        return $this->getData(self::MARK_AS_SENT);
    }

    /**
     * Set Mark As Sent
     *
     * @param mixed $markAsSent
     * @return MessageInterface
     */
    public function setMarkAsSent($markAsSent): MessageInterface
    {
        return $this->setData(self::MARK_AS_SENT, $markAsSent);
    }
}
