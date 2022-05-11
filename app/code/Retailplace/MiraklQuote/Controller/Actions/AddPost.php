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
use Magento\Checkout\Model\Session;
use Magento\Customer\Controller\AbstractAccount as CustomerController;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteRequestReturnCollection;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;
use Mirakl\Connector\Helper\Shop as ShopHelper;

/**
 * Class AddPost
 */
class AddPost extends CustomerController implements HttpPostActionInterface
{
    /** @var \Retailplace\MiraklQuote\Model\MiraklQuoteManagement */
    private $miraklQuoteManagement;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Mirakl\Connector\Helper\Shop */
    private $shopHelper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Retailplace\MiraklQuote\Model\MiraklQuoteManagement $miraklQuoteManagement
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Mirakl\Connector\Helper\Shop $shopHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        MiraklQuoteManagement $miraklQuoteManagement,
        Session $checkoutSession,
        ShopHelper $shopHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->miraklQuoteManagement = $miraklQuoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->shopHelper = $shopHelper;
        $this->logger = $logger;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quoteMessage = (string) $this->getRequest()->getParam('quote_message');
        $sellerId = (string) $this->getRequest()->getParam('seller_id');
        /** @var \Retailplace\MiraklShop\Api\Data\ShopInterface $shop */
        $shop = $this->shopHelper->getShopById($sellerId);

        if ($shop && $shop->getId()) {
            if ($shop->getAllowQuoteRequests()) {
                if ($shop->getShopAmounts()->getIsMinQuoteAmountReached()) {
                    try {
                        $response = $this->miraklQuoteManagement->createMiraklQuote($this->checkoutSession->getQuote(),
                            $quoteMessage, $sellerId);
                        $result = $this->sendResponse($response);
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                        $result = $this->sendResponse(null);
                    }
                } else {
                    $result = $this->sendResponse(
                        null,
                        'Minimum Quote Amount for the Seller is not reached'
                    );
                }
            } else {
                $result = $this->sendResponse(
                    null,
                    'This Seller does not allow Quote Requests'
                );
            }
        } else {
            $result = $this->sendResponse(null);
            $this->logger->error('Shop not found: ' . $sellerId);
        }

        return $result;
    }

    /**
     * Generate Json Response
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteRequestReturnCollection|null $response
     * @param string|null $responseMessage
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function sendResponse(?QuoteRequestReturnCollection $response, ?string $responseMessage = null): ResultInterface
    {
        if ($response) {
            $result = $response->first();
            $errors = $result->getQuoteRequestError();

            if ($errors) {
                $data = [
                    'is_success' => false,
                    'response' => ['error' => $errors->getErrors()->toArray()]
                ];
            } else {
                $data = [
                    'is_success' => true,
                    'response' => ['id' => $result->getQuoteRequest()->getId()]
                ];
            }
        } else {
            $errorMessage = __($responseMessage ?: 'Quote Creation Error.');
            $data = [
                'is_success' => false,
                'response'   => [
                    'error' => [
                        ['field' => 'Seller', 'message' => $errorMessage]
                    ]
                ]
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
