<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Controller\Actions;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Controller\AbstractAccount as CustomerController;
use Magento\Quote\Api\Data\CartInterface;
use Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;
use Retailplace\MiraklQuote\Model\QuoteDataUpdater;

/**
 * Class ViewPost
 */
class ViewPost extends CustomerController implements HttpPostActionInterface
{
    /** @var \Retailplace\MiraklQuote\Model\MiraklQuoteManagement */
    private $miraklQuoteManagement;

    /** @var \Retailplace\MiraklQuote\Model\QuoteDataUpdater */
    private $quoteDataUpdater;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Retailplace\MiraklQuote\Model\MiraklQuoteManagement $miraklQuoteManagement
     * @param \Retailplace\MiraklQuote\Model\QuoteDataUpdater $quoteDataUpdater
     */
    public function __construct(
        Context $context,
        MiraklQuoteManagement $miraklQuoteManagement,
        QuoteDataUpdater $quoteDataUpdater
    ) {
        parent::__construct($context);

        $this->miraklQuoteManagement = $miraklQuoteManagement;
        $this->quoteDataUpdater = $quoteDataUpdater;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quoteRequestId = $this->getRequest()->getParam('id');
        $miraklQuoteRequest = $this->miraklQuoteManagement->getQuoteRequestById($quoteRequestId);
        try {
            $quote = $this->miraklQuoteManagement->getQuoteByMiraklQuote($miraklQuoteRequest);
            $this->quoteDataUpdater->setCurrentStep($miraklQuoteRequest);
            $this->quoteDataUpdater->setTotals($miraklQuoteRequest, $quote);
            $this->quoteDataUpdater->extendWithProducts($miraklQuoteRequest);
        } catch (\Exception $e) {
            $quote = null;
            $miraklQuoteRequest = null;
            $messages = array_unique(explode("\n", $e->getMessage()));
            foreach ($messages as $message) {
                $this->messageManager->addErrorMessage($message);
            }
        }

        return $this->sendResponse($miraklQuoteRequest, $quote);
    }

    /**
     * Send Json Response
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $quoteRequest
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function sendResponse(?QuoteRequest $quoteRequest, ?CartInterface $quote = null): ResultInterface
    {
        if ($quoteRequest) {
            $quoteItems = [];
            $quoteArray = [];

            if ($quote) {
                foreach ($quote->getAllItems() as $quoteItem) {
                    $quoteItems[] = $quoteItem->toArray();
                }
                $quoteArray = $quote->toArray();
            }

            $data = [
                'is_success' => true,
                'response' => [
                    'mirakl_quote_request' => $quoteRequest->toArray(),
                    'quote' => $quoteArray,
                    'quote_items' => $quoteItems
                ]
            ];
        } else {
            $data = [
                'is_success' => false,
                'response' => __('Quote Obtaining Error.')
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
