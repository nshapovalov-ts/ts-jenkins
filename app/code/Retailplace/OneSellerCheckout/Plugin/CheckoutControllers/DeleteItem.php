<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Plugin\CheckoutControllers;

use Magento\Checkout\Controller\Cart\Delete;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteItem
 */
class DeleteItem
{
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Magento\Quote\Model\ResourceModel\Quote */
    private $quoteResourceModel;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Magento\Quote\Model\QuoteRepository\LoadHandler */
    private $loadHandler;

    /** @var \Mirakl\Connector\Model\Quote\Updater */
    private $quoteUpdater;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * DeleteItem Constructor
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Mirakl\Connector\Model\Quote\Updater $quoteUpdater
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        Session $checkoutSession,
        QuoteResourceModel $quoteResourceModel,
        QuoteFactory $quoteFactory,
        LoadHandler $loadHandler,
        QuoteUpdater $quoteUpdater,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->quoteFactory = $quoteFactory;
        $this->loadHandler = $loadHandler;
        $this->quoteUpdater = $quoteUpdater;
        $this->logger = $logger;
    }

    /**
     * Remove Quote Item from Child Quote when it was deleted from Parent Quote
     *
     * @param \Magento\Checkout\Controller\Cart\Delete $controller
     * @param \Magento\Framework\Controller\Result\Redirect|null $result
     * @return \Magento\Framework\Controller\Result\Redirect|null
     */
    public function afterExecute(Delete $controller, ?Redirect $result): ?Redirect
    {
        $id = (int) $this->request->getParam('id');
        if ($id) {
            try {
                $quote = $this->checkoutSession->getQuote();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $quote = null;
            }

            if ($quote) {
                $item = $quote->getItemById($id);
                if ($item) {
                    $shopId = $item->getData('mirakl_shop_id');
                    $childQuote = $this->quoteFactory->create();
                    $this->quoteResourceModel->loadByCustomerId($childQuote, $quote->getCustomerId(), $shopId, true);
                    $this->loadHandler->load($childQuote);
                    if ($childQuote->getId()) {
                        foreach ($childQuote->getItems() as $childQuoteItem) {
                            if ($childQuoteItem->getSku() == $item->getSku()) {
                                $childQuote->removeItem($childQuoteItem->getItemId());
                                $this->quoteUpdater->synchronize($childQuote);
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
