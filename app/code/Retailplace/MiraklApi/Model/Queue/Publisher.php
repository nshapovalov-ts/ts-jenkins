<?php

/**
 * Retailplace_MiraklApi
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklApi\Model\Queue;

use InvalidArgumentException;
use Magento\Framework\MessageQueue\Publisher as QueuePublisher;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklApi\Api\Data\Queue\MessageInterface;
use Retailplace\MiraklApi\Api\Data\Queue\MessageInterfaceFactory;

/**
 * Publisher class
 */
class Publisher
{
    /** @var string */
    const TOPIC_NAME = 'retailplace.mirakl.order.create';

    /** @var QueuePublisher */
    private $queuePublisher;

    /** @var MessageInterfaceFactory */
    private $messageInterfaceFactory;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Publisher constructor
     *
     * @param QueuePublisher $queuePublisher
     * @param MessageInterfaceFactory $messageInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        QueuePublisher $queuePublisher,
        MessageInterfaceFactory $messageInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->queuePublisher = $queuePublisher;
        $this->messageInterfaceFactory = $messageInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * Add to queue
     *
     * @param int $orderId
     * @param mixed $markAsSent
     */
    public function addToQueue(int $orderId, $markAsSent)
    {
        /** @var MessageInterface $message */
        $message = $this->messageInterfaceFactory->create();
        $message->setOrderId($orderId);
        $message->setMarkAsSent($markAsSent);
        try {
            $this->queuePublisher->publish(self::TOPIC_NAME, $message);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
