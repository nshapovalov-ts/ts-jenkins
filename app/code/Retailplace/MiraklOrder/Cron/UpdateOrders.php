<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Cron;

use Retailplace\MiraklOrder\Model\MiraklOrderUpdaterFactory;
use Psr\Log\LoggerInterface;

/**
 * Class MiraklOrder implements cron model for updating Mirakl orders
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateOrders
{
    /** @var MiraklOrderUpdaterFactory */
    private $miraklOrderUpdaterFactory;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param MiraklOrderUpdaterFactory $miraklOrderUpdaterFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        MiraklOrderUpdaterFactory $miraklOrderUpdaterFactory,
        LoggerInterface $logger
    ) {
        $this->miraklOrderUpdaterFactory = $miraklOrderUpdaterFactory;
        $this->logger = $logger;
    }

    /**
     * Update all Mirakl orders
     *
     * @return void
     */
    public function updateMiraklOrders()
    {
        try {
            $miraklOrderUpdater = $this->miraklOrderUpdaterFactory->create();
            $miraklOrderUpdater->update();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
