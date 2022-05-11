<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklQuote\Model\MiraklOfferUpdater;

/**
 * Class UpdateOffersData
 */
class UpdateOffersData
{
    /** @var \Retailplace\MiraklQuote\Model\MiraklOfferUpdater */
    private $miraklOfferUpdater;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Retailplace\MiraklQuote\Model\MiraklOfferUpdater $miraklOfferUpdater
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        MiraklOfferUpdater $miraklOfferUpdater,
        LoggerInterface $logger
    ) {
        $this->miraklOfferUpdater = $miraklOfferUpdater;
        $this->logger = $logger;
    }

    /**
     * Update Allow Quote Requests field
     */
    public function updateIsQuotable()
    {
        try {
            $this->miraklOfferUpdater->updateOffers();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
