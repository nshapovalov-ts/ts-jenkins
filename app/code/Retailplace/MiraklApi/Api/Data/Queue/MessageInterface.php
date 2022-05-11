<?php

/**
 * Retailplace_MiraklApi
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklApi\Api\Data\Queue;

/**
 * MessageInterface interface
 */
interface MessageInterface
{
    /** @var string */
    public const ORDER_ID = 'order_id';
    public const MARK_AS_SENT = 'mark_as_sent';

    /**
     * Get Order ID
     *
     * @return int
     */
    public function getOrderId(): int;

    /**
     * Set Order ID
     *
     * @param int $orderId
     * @return MessageInterface
     */
    public function setOrderId(int $orderId): MessageInterface;

    /**
     * Get Mark As Sent
     *
     * @return mixed
     */
    public function getMarkAsSent();

    /**
     * Set Mark As Sent
     *
     * @param mixed $markAsSent
     * @return MessageInterface
     */
    public function setMarkAsSent($markAsSent): MessageInterface;
}
