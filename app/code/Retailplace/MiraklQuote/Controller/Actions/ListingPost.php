<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Controller\Actions;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Controller\AbstractAccount as CustomerController;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;
use Retailplace\MiraklQuote\Model\QuoteDataUpdater;

/**
 * Class ListingPost
 */
class ListingPost extends CustomerController implements HttpPostActionInterface
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
        $quotesList = $this->miraklQuoteManagement->getQuotesForCustomer();
        $pager = $this->quoteDataUpdater->getPagination($quotesList);

        return $this->sendResponse($quotesList, $pager);
    }

    /**
     * Send Json Response
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection|null $response
     * @param string|null $pager
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function sendResponse(?QuoteRequestCollection $response, ?string $pager): ResultInterface
    {
        if ($response) {
            $data = [
                'is_success' => true,
                'response' => ['quoteRequests' => $response->toArray(), 'pagination' => $pager]
            ];
        } else {
            $data = [
                'is_success' => false,
                'response'   => __('Unable to get Quotes List.')
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
