<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Plugin\Model;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;

/**
 * Class QuoteAddMiraklData
 */
class QuoteAddMiraklData
{
    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Mirakl\Connector\Model\Quote\Updater */
    private $quoteUpdater;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * QuoteAddMiraklData constructor.
     *
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Mirakl\Connector\Model\Quote\Updater $quoteUpdater
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        OfferRepositoryInterface $offerRepository,
        QuoteUpdater $quoteUpdater,
        LoggerInterface $logger
    ) {
        $this->offerRepository = $offerRepository;
        $this->quoteUpdater = $quoteUpdater;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote\Item|string $result
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\DataObject|null|bool $request
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    public function afterAddProduct(Quote $subject, $result, Product $product, $request = null)
    {
        if (is_object($request) && $request->getOfferId() && is_object($result)) {
            try {
                $offer = $this->offerRepository->getById((int) $request->getOfferId());
                $this->quoteUpdater->setShopToItem($result, $offer);
                $result->setMiraklOfferId($offer->getId());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $result;
    }
}
