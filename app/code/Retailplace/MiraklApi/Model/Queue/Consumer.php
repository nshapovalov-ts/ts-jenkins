<?php

/**
 * Retailplace_MiraklApi
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklApi\Model\Queue;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklApi\Api\Data\Queue\MessageInterface;
use Retailplace\MiraklApi\Helper\Order;

/**
 * Consumer class
 */
class Consumer
{
    /** @var Order */
    private $miraklOrderHelper;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Consumer constructor
     *
     * @param Order $miraklOrderHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Order $miraklOrderHelper,
        LoggerInterface $logger
    ) {
        $this->miraklOrderHelper = $miraklOrderHelper;
        $this->logger = $logger;
    }

    /**
     * Process queue
     *
     * @param MessageInterface $message
     * @throws LocalizedException
     */
    public function process(MessageInterface $message)
    {
        try {
            $this->miraklOrderHelper->process($message->getOrderId(), $message->getMarkAsSent());
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
            throw $e;
        }
    }
}
