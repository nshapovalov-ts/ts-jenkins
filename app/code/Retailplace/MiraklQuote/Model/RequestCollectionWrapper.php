<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Model;

use Exception;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Psr\Log\LoggerInterface;

/**
 * Class RequestCollectionWrapper
 */
class RequestCollectionWrapper extends Collection
{
    /** @var \Magento\Framework\DataObject\Factory */
    private $dataObjectFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        DataObjectFactory $dataObjectFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($entityFactory);

        $this->dataObjectFactory = $dataObjectFactory;
        $this->logger = $logger;
    }

    /**
     * Ignore Collection Loading attempts
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return bool
     */
    public function load($printQuery = false, $logQuery = false)
    {
        return true;
    }

    /**
     * Setter for Collection Data
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection|null $quoteRequestCollection
     * @return $this
     */
    public function setRequestCollection(?QuoteRequestCollection $quoteRequestCollection): RequestCollectionWrapper
    {
        if ($quoteRequestCollection) {
            foreach ($quoteRequestCollection as $quoteRequest) {
                $quoteRequestDataObject = $this->dataObjectFactory->create($quoteRequest->toArray());
                try {
                    $this->addItem($quoteRequestDataObject);
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }

            $this->setTotalRecords((int) $quoteRequestCollection->getTotalCount());
        }

        return $this;
    }

    /**
     * Collection Size setter
     *
     * @param int $totalRecords
     * @return \Retailplace\MiraklQuote\Model\RequestCollectionWrapper
     */
    private function setTotalRecords(int $totalRecords): RequestCollectionWrapper
    {
        $this->_totalRecords = $totalRecords;

        return $this;
    }

    /**
     * Ignore actual $_items size
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->_totalRecords;
    }
}
