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
use Mirakl\Core\Model\Shop as ShopModel;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;
use Mirakl\Connector\Helper\Shop as ShopHelper;

/**
 * Class AddPost
 */
class AddRenderPost extends CustomerController implements HttpPostActionInterface
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
        $sellerId = (int) $this->getRequest()->getParam('seller_id');
        try {
            /** @var \Retailplace\MiraklShop\Api\Data\ShopInterface $shop */
            $shop = $this->shopHelper->getShopById($sellerId);
            if ($shop->getAllowQuoteRequests()) {
                if ($shop->getShopAmounts()->getIsMinQuoteAmountReached()) {
                    $quoteLinesCollection = $this->miraklQuoteManagement->getQuoteLinesCollection(
                        $this->checkoutSession->getQuote(),
                        $sellerId
                    );
                    $result = $this->sendResponse($quoteLinesCollection, $shop);
                } else {
                    $result = $this->sendResponse(
                        null,
                        null,
                        'Minimum Quote Amount for the Seller is not reached'
                    );
                }
            } else {
                $result = $this->sendResponse(
                    null,
                    null,
                    'This Seller does not allow Quote Requests'
                );
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $result = $this->sendResponse(null, null);
        }

        return $result;
    }

    /**
     * Generate Json Response
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection|null $quoteLineCollection
     * @param \Mirakl\Core\Model\Shop|null $shop
     * @param string|null $responseMessage
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function sendResponse(
        ?QuoteLineCollection $quoteLineCollection,
        ?ShopModel $shop,
        ?string $responseMessage = null
    ): ResultInterface {
        if ($shop && $shop->getId() && $quoteLineCollection && $quoteLineCollection->count()) {
            $data = [
                'is_success' => true,
                'response' => [
                    'quote_line_collection' => $quoteLineCollection->toArray(),
                    'shop' => [
                        'name' => $shop->getName(),
                        'url' => $this->_url->getUrl('marketplace/shop/view', ['id' => $shop->getId()])
                        ]
                ]
            ];
        } else {
            $data = [
                'is_success' => false,
                'response'   => __($responseMessage ?: 'There are no Products of this Seller in the Cart')
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
