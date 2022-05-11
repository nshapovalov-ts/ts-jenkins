<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Plugin;

use Exception;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;

/**
 * Class QuoteToOrder
 */
class QuoteToOrder
{
    /** @var \Retailplace\MultiQuote\Model\QuoteResource */
    private $quoteResourceModel;

    /** @var \Magento\Quote\Model\QuoteRepository\LoadHandler */
    private $loadHandler;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * QuoteToOrder Constructor
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        QuoteResourceModel $quoteResourceModel,
        QuoteFactory $quoteFactory,
        LoadHandler $loadHandler,
        LoggerInterface $logger
    ) {
        $this->quoteResourceModel = $quoteResourceModel;
        $this->quoteFactory = $quoteFactory;
        $this->loadHandler = $loadHandler;
        $this->logger = $logger;
    }

    /**
     * Remove Quote Items from Parent Quote after Child Quote Submit
     * Remove Child Quotes when Parent Quote Submit
     *
     * @param \Magento\Quote\Api\CartManagementInterface $subject
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function afterSubmit(CartManagementInterface $subject, OrderInterface $order, QuoteEntity $quote): OrderInterface
    {
        $parentId = $quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID);
        $sellerId = $quote->getData(OneSellerQuoteAttributes::QUOTE_SELLER_ID);

        if ($parentId) {
            try {
                $parentQuote = $this->quoteFactory->create();
                $this->quoteResourceModel->loadByCustomerId($parentQuote, $quote->getCustomerId(), 0, true);
                $this->loadHandler->load($parentQuote);
                if ($parentQuote->getId()) {
                    $changesFlag = false;
                    foreach ($parentQuote->getItems() as $parentQuoteItem) {
                        if ($parentQuoteItem->getData('mirakl_shop_id') == $sellerId) {
                            $parentQuote->removeItem($parentQuoteItem->getItemId());
                            $changesFlag = true;
                        }
                    }

                    if ($changesFlag) {
                        $parentQuote->collectTotals();
                        $this->quoteResourceModel->save($parentQuote);
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        } else {
            $this->quoteResourceModel->removeChildQuotes($quote->getId());
        }

        return $order;
    }
}
