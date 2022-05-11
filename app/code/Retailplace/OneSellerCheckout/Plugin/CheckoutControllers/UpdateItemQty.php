<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Plugin\CheckoutControllers;

use Magento\Checkout\Controller\Cart\UpdateItemQty as MagentoUpdateItemQty;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository\LoadHandler;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateItemQty
 */
class UpdateItemQty
{
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Magento\Checkout\Model\Cart\RequestQuantityProcessor */
    private $requestQuantityProcessor;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Retailplace\MultiQuote\Model\QuoteResource */
    private $quoteResourceModel;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Magento\Quote\Model\QuoteRepository\LoadHandler */
    private $loadHandler;

    /** @var \Mirakl\Connector\Model\Quote\Updater */
    private $quoteUpdater;

    /** @var \Magento\Framework\App\Response\Http */
    private $httpResponse;

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    private $json;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * UpdateItemQty Constructor
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Checkout\Model\Cart\RequestQuantityProcessor $requestQuantityProcessor
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Mirakl\Connector\Model\Quote\Updater $quoteUpdater
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Magento\Framework\App\Response\Http $httpResponse
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        RequestQuantityProcessor $requestQuantityProcessor,
        Session $checkoutSession,
        QuoteResourceModel $quoteResourceModel,
        QuoteFactory $quoteFactory,
        QuoteUpdater $quoteUpdater,
        LoadHandler $loadHandler,
        Http $httpResponse,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->requestQuantityProcessor = $requestQuantityProcessor;
        $this->checkoutSession = $checkoutSession;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->quoteFactory = $quoteFactory;
        $this->quoteUpdater = $quoteUpdater;
        $this->loadHandler = $loadHandler;
        $this->httpResponse = $httpResponse;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * Update Quote Item Qty on Child Quote when it was changed on Parent Quote
     *
     * @param \Magento\Checkout\Controller\Cart\UpdateItemQty $controller
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(MagentoUpdateItemQty $controller)
    {
        $cartData = $this->request->getParam('cart');
        $cartData = $this->requestQuantityProcessor->process($cartData);
        $quote = $this->checkoutSession->getQuote();
        foreach ($cartData as $itemId => $itemInfo) {
            $item = $quote->getItemById($itemId);
            $qty = isset($itemInfo['qty']) ? (double) $itemInfo['qty'] : 0;
            if ($item) {
                if ($item->getOrigData('qty') != $qty) {
                    $shopId = $item->getData('mirakl_shop_id');
                    $childQuote = $this->quoteFactory->create();
                    $this->quoteResourceModel->loadByCustomerId($childQuote, $quote->getCustomerId(), $shopId, true);
                    $this->loadHandler->load($childQuote);
                    if ($childQuote->getId()) {
                        foreach ($childQuote->getItems() as $childQuoteItem) {
                            if ($childQuoteItem->getSku() == $item->getSku()) {
                                $childQuoteItem->clearMessage();
                                $childQuoteItem->setQty($qty);
                                if ($childQuoteItem->getHasError()) {
                                    $this->logger->error($childQuoteItem->getMessage());
                                    $this->jsonResponse($childQuoteItem->getMessage());
                                }
                            }
                        }

                        $this->quoteUpdater->synchronize($childQuote);
                    }
                }
            }
        }
    }

    /**
     * JSON response builder.
     *
     * @param string $error
     */
    private function jsonResponse(string $error = '')
    {
        $response = ['success' => true];
        if ($error) {
            $response = [
                'success' => false,
                'error_message' => $error,
            ];
        }

        $this->httpResponse->representJson($this->json->serialize($response));
    }
}
