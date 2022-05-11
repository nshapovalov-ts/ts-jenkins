<?php

/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MultiQuote\Model;

use Exception;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use Magento\Quote\Model\QuoteRepository\SaveHandler;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\SalesSequence\Model\Manager;
use Retailplace\OneSellerCheckout\Model\SellerQuoteManagement;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;

/**
 * Class QuoteResource
 */
class QuoteResource extends QuoteResourceModel
{
    /** @var \Magento\Quote\Model\QuoteRepository\LoadHandler */
    private $loadHandler;

    /** @var \Magento\Quote\Model\QuoteRepository\SaveHandler */
    private $saveHandler;

    /** @var \Retailplace\OneSellerCheckout\Model\SellerQuoteManagement */
    private $sellerQuoteManagement;

    /** @var \Retailplace\MiraklQuote\Model\MiraklQuoteManagement */
    private $miraklQuoteManagement;

    /**
     * QuoteResource Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param \Retailplace\OneSellerCheckout\Model\SellerQuoteManagement $sellerQuoteManagement
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Magento\Quote\Model\QuoteRepository\SaveHandler $saveHandler
     * @param \Retailplace\MiraklQuote\Model\MiraklQuoteManagement $miraklQuoteManagement
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        Manager $sequenceManager,
        SellerQuoteManagement $sellerQuoteManagement,
        LoadHandler $loadHandler,
        SaveHandler $saveHandler,
        MiraklQuoteManagement $miraklQuoteManagement,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $sequenceManager,
            $connectionName
        );

        $this->sellerQuoteManagement = $sellerQuoteManagement;
        $this->loadHandler = $loadHandler;
        $this->saveHandler = $saveHandler;
        $this->miraklQuoteManagement = $miraklQuoteManagement;
    }

    /**
     * Load quote data by customer identifier
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $customerId
     * @param int|null $forceSellerId
     * @param bool $preventCreation
     * @return $this
     */
    public function loadByCustomerId($quote, $customerId, $forceSellerId = null, $preventCreation = false)
    {
        $connection = $this->getConnection();

        if ($forceSellerId !== null) {
            $sellerId = (int) $forceSellerId;
        } else {
            $sellerId = $this->sellerQuoteManagement->getSellerIdFromRequest();
        }

        $select = $this->getQuoteSelect($quote, (int) $customerId, $sellerId);
        $data = $connection->fetchRow($select);

        if (!$data && $sellerId && !$preventCreation) {
            $select = $this->getQuoteSelect($quote, $customerId);
            $data = $connection->fetchRow($select);
            if ($data) {
                $parentQuote = $this->sellerQuoteManagement->createQuoteFromData($data);

                $this->_afterLoad($parentQuote);
                $this->loadHandler->load($parentQuote);
                $this->sellerQuoteManagement->filterItemsByShop($parentQuote, $sellerId);
                $newQuote = $this->sellerQuoteManagement->cloneQuote($parentQuote);
                try {
                    $this->saveHandler->save($newQuote);
                } catch (Exception $e) {
                    $this->_logger->error($e->getMessage());
                }

                $quote = $newQuote;
            }
        }

        if ($data) {
            $quote->setData($data);
            $quote->setOrigData();
        }

        $this->_afterLoad($quote);

        return $this;
    }

    /**
     * Remove all Quote Sub Quotes
     *
     * @param int $quoteId
     * @param int|null $ignoreQuoteId
     */
    public function removeChildQuotes($quoteId, $ignoreQuoteId = null)
    {
        if ($quoteId) {
            $where = [
                'parent_quote_id = ?' => $quoteId,
                'is_active = ?' => 1
            ];

            if ($ignoreQuoteId) {
                $where = array_merge(
                    $where,
                    ['entity_id != ?' => $ignoreQuoteId]
                );
            }

            try {
                $this->getConnection()->delete(
                    $this->getMainTable(),
                    $where
                );
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        }
    }

    /**
     * Generate Select for Quote Getting Considering Seller ID
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $customerId
     * @param int|null $sellerId
     * @return \Magento\Framework\DB\Select
     */
    private function getQuoteSelect(Quote $quote, int $customerId, ?int $sellerId = null): Select
    {
        $select = $this->_getLoadSelect(
            'customer_id',
            $customerId,
            $quote
        )->where(
            'is_active = ?',
            1
        )->order(
            'updated_at ' . Select::SQL_DESC
        )->limit(
            1
        );

        $miraklQuoteId = $this->miraklQuoteManagement->getMiraklQuoteIdFromRequest();
        if ($sellerId) {
            $select->where('seller_id = ?', $sellerId);
        } elseif ($miraklQuoteId) {
            $select->where('mirakl_quote_id = ?', $miraklQuoteId);
        } else {
            $select
                ->where('seller_id IS NULL OR seller_id = 0')
                ->where('mirakl_quote_id is NULL');
        }

        return $select;
    }
}
