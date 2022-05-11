<?php

/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MultiQuote\Model;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use Magento\Quote\Model\QuoteRepository\SaveHandler;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Psr\Log\LoggerInterface;

/**
 * Class QuoteHandlers
 */
class QuoteHandlers
{
    /** @var \Magento\Quote\Model\ResourceModel\Quote */
    private $quoteResourceModel;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Magento\Quote\Model\QuoteRepository\LoadHandler */
    private $loadHandler;

    /** @var \Magento\Quote\Model\QuoteRepository\SaveHandler */
    private $saveHandler;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * QuoteHandlers Constructor
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Magento\Quote\Model\QuoteRepository\SaveHandler $saveHandler
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        QuoteResourceModel $quoteResourceModel,
        QuoteFactory $quoteFactory,
        LoadHandler $loadHandler,
        SaveHandler $saveHandler,
        LoggerInterface $logger
    ) {
        $this->quoteResourceModel = $quoteResourceModel;
        $this->quoteFactory = $quoteFactory;
        $this->loadHandler = $loadHandler;
        $this->saveHandler = $saveHandler;
        $this->logger = $logger;
    }

    /**
     * Load Quote by Id
     *
     * @param int $quoteId
     * @param bool $skipRelatedObjects
     * @return \Magento\Quote\Model\Quote
     */
    public function loadQuoteById($quoteId, bool $skipRelatedObjects = false): Quote
    {
        $quote = $this->quoteFactory->create();
        $this->quoteResourceModel->loadByIdWithoutStore($quote, $quoteId);
        if (!$skipRelatedObjects) {
            $this->loadHandler->load($quote);
        }

        return $quote;
    }

    /**
     * Save Quote to DB
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote
     */
    public function saveQuote(Quote $quote): Quote
    {
        try {
            $this->saveHandler->save($quote);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $quote;
    }
}
