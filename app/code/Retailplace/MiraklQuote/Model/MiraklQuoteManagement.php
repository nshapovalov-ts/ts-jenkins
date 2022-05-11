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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mirakl\Api\Helper\ClientHelper\MMP;
use Mirakl\Connector\Helper\Config as MiraklConfig;
use Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteRequestReturnCollection;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection;
use Mirakl\MMP\Front\Domain\Quote\Get\Quote;
use Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine;
use Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest;
use Mirakl\MMP\Front\Domain\Quote\Get\ShopQuoteRequest;
use Mirakl\MMP\Front\Domain\Quote\Message\CreatedQuoteRequestMessage;
use Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer;
use Mirakl\MMP\Front\Domain\Shipping\ShippingRateOfferFactory;
use Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;
use Mirakl\Mcm\Helper\Data as MiraklMcmHelper;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Mirakl\MMP\Front\Domain\Quote\Get\Quote as MiraklQuote;
use Mirakl\Connector\Model\Quote\Updater as MiraklQuoteUpdater;

/**
 * Class MiraklQuoteManagement
 */
class MiraklQuoteManagement
{
    /** @var string */
    public const REQUEST_PARAM_MIRAKL_QUOTE_ID = 'mirakl-quote';

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magento\Quote\Api\Data\CartInterfaceFactory */
    private $quoteFactory;

    /** @var \Magento\Quote\Api\CartRepositoryInterface */
    private $quoteRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var \Magento\Framework\DataObject\Factory */
    private $dataObjectFactory;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Mirakl\Mcm\Helper\Data */
    private $miraklMcmHelper;

    /** @var \Retailplace\MiraklQuote\Model\QuoteDataUpdater */
    private $quoteDataUpdater;

    /** @var \Mirakl\Api\Helper\ClientHelper\MMP */
    private $miraklApiClient;

    /** @var \Retailplace\MiraklQuote\Model\QuoteRequestBuilder */
    private $quoteRequestBuilder;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /** @var \Magento\Sales\Model\ResourceModel\Order */
    private $orderResourceModel;

    /** @var \Mirakl\Connector\Helper\Config */
    private $config;

    /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOfferFactory */
    private $shippingRateOfferFactory;

    /** @var \Mirakl\Connector\Model\Quote\Updater */
    private $miraklQuoteUpdater;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Mirakl\Mcm\Helper\Data $miraklMcmHelper
     * @param \Retailplace\MiraklQuote\Model\QuoteDataUpdater $quoteDataUpdater
     * @param \Mirakl\Api\Helper\ClientHelper\MMP $miraklApiClient
     * @param \Retailplace\MiraklQuote\Model\QuoteRequestBuilder $quoteRequestBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResourceModel
     * @param \Mirakl\Connector\Helper\Config $config
     * @param \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOfferFactory $shippingRateOfferFactory
     * @param \Mirakl\Connector\Model\Quote\Updater $miraklQuoteUpdater
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        CustomerSession $customerSession,
        CartInterfaceFactory $quoteFactory,
        CartRepositoryInterface $quoteRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ProductRepositoryInterface $productRepository,
        DataObjectFactory $dataObjectFactory,
        CustomerRepositoryInterface $customerRepository,
        MiraklMcmHelper $miraklMcmHelper,
        QuoteDataUpdater $quoteDataUpdater,
        MMP $miraklApiClient,
        QuoteRequestBuilder $quoteRequestBuilder,
        RequestInterface $request,
        OrderResourceModel $orderResourceModel,
        MiraklConfig $config,
        ShippingRateOfferFactory $shippingRateOfferFactory,
        MiraklQuoteUpdater $miraklQuoteUpdater,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->productRepository = $productRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->customerRepository = $customerRepository;
        $this->miraklMcmHelper = $miraklMcmHelper;
        $this->quoteDataUpdater = $quoteDataUpdater;
        $this->miraklApiClient = $miraklApiClient;
        $this->quoteRequestBuilder = $quoteRequestBuilder;
        $this->request = $request;
        $this->orderResourceModel = $orderResourceModel;
        $this->config = $config;
        $this->shippingRateOfferFactory = $shippingRateOfferFactory;
        $this->miraklQuoteUpdater = $miraklQuoteUpdater;
        $this->logger = $logger;
    }

    /**
     * Send Quote Creation Request to Mirakl
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $quoteMessage
     * @param string $sellerId
     * @return \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteRequestReturnCollection|null
     */
    public function createMiraklQuote(CartInterface $quote, string $quoteMessage, string $sellerId): ?QuoteRequestReturnCollection
    {
        $addingQuoteRequest = $this->quoteRequestBuilder->createMiraklQuoteAddingRequest($quote, $quoteMessage, $sellerId);
        try {
            /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteRequestReturnCollection $response */
            $response = $this->miraklApiClient->send($addingQuoteRequest);
        } catch (Exception $e) {
            $response = null;
            $this->logger->error($e->getMessage());
        }

        return $response ?: null;
    }

    /**
     * Get Quote Request from Mirakl by ID
     *
     * @param string $quoteRequestId
     * @return \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null
     */
    public function getQuoteRequestById(string $quoteRequestId): ?QuoteRequest
    {
        $getQuoteRequest = $this->quoteRequestBuilder->getQuoteRequestsRequest();
        $getQuoteRequest->setShopQuoteRequestId($quoteRequestId);
        $getQuoteRequest->setCustomerIds([$this->getCustomerId()]);

        try {
            /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection $response */
            $response = $this->miraklApiClient->send($getQuoteRequest);
            $this->quoteDataUpdater->extendQuoteRequestCollection($response);
            $this->quoteDataUpdater->cleanQuotesList($response);
        } catch (Exception $e) {
            $response = null;
            $this->logger->error($e->getMessage());
        }

        return $response && $response->first() ? $response->first() : null;
    }

    /**
     * Get Quotes list from Mirakl by Customer ID
     *
     * @return \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection|null
     */
    public function getQuotesForCustomer(): ?QuoteRequestCollection
    {
        $currentPage = $this->quoteDataUpdater->getCurrentPage();
        $offset = $currentPage ? $currentPage - 1 : 0;
        $getQuoteRequest = $this->quoteRequestBuilder->getQuoteRequestsRequest(
            QuoteDataUpdater::REQUESTS_PER_PAGE, $offset * QuoteDataUpdater::REQUESTS_PER_PAGE
        );
        $getQuoteRequest->setCustomerIds([$this->getCustomerId()]);

        try {
            /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection $response */
            $response = $this->miraklApiClient->send($getQuoteRequest);
            $this->quoteDataUpdater->extendQuoteRequestCollection($response);
            $this->quoteDataUpdater->cleanQuotesList($response);
        } catch (Exception $e) {
            $response = null;
            $this->logger->error($e->getMessage());
        }

        return $response ?: null;
    }

    /**
     * Fetch Magento Quote by Mirakl Quote
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $miraklQuoteRequest
     * @return \Magento\Quote\Api\Data\CartInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteByMiraklQuote(?QuoteRequest $miraklQuoteRequest): ?CartInterface
    {
        $quote = null;

        if ($miraklQuoteRequest->getShopQuoteRequests()->first()->getState() != ShopQuoteRequest::ACCEPTED) {
            $miraklQuote = $this->getLastQuoteFromRequest($miraklQuoteRequest);
            if ($miraklQuote && $miraklQuote->getState() != MiraklQuote::EXPIRED) {
                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteria = $searchCriteriaBuilder
                    ->addFilter(MiraklQuoteAttributes::MIRAKL_QUOTE_ID, $miraklQuote->getId())
                    ->addFilter(CartInterface::KEY_IS_ACTIVE, 1)
                    ->create();
                $quotes = $this->quoteRepository->getList($searchCriteria);
                if ($quotes->getTotalCount()) {
                    foreach ($quotes->getItems() as $quoteElement) {
                        $quote = $quoteElement;
                        break;
                    }
                } else {
                    $quote = $this->convertToMagentoQuote($miraklQuoteRequest, $miraklQuote->getId());
                }
            }
        }

        return $quote;
    }

    /**
     * Get Mirakl Quote ID param from Request
     *
     * @return string
     */
    public function getMiraklQuoteIdFromRequest(): string
    {
        return (string) $this->request->getParam(self::REQUEST_PARAM_MIRAKL_QUOTE_ID);
    }

    /**
     * Create Mirakl Order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @return \Mirakl\MMP\FrontOperator\Domain\Order|null
     */
    public function createMiraklOrder(OrderInterface $order): ?OrderCollection
    {
        $placeOrderRequest = $this->quoteRequestBuilder->getPlaceOrderFromQuoteRequest($order);
        try {
            /** @var \Mirakl\MMP\FrontOperator\Domain\Collection\Order\OrderCollection $response */
            $response = $this->miraklApiClient->send($placeOrderRequest);
            $order->setData('mirakl_sent', 1);
            $this->orderResourceModel->saveAttribute($order, 'mirakl_sent');
        } catch (Exception $e) {
            $response = null;
            $this->logger->error($e->getMessage());
        }

        return $response ?? null;
    }

    /**
     * Send Quote Message
     *
     * @param string $shopQuoteRequestId
     * @param string $message
     * @param string|null $toType
     * @return \Mirakl\MMP\Front\Domain\Quote\Message\CreatedQuoteRequestMessage|null
     */
    public function sendMessage(string $shopQuoteRequestId, string $message, ?string $toType = null): ?CreatedQuoteRequestMessage
    {
        $quoteMessageRequest = $this->quoteRequestBuilder
            ->getQuoteMessageRequest($shopQuoteRequestId, $message, $toType);

        /** @var \Mirakl\MMP\Front\Domain\Quote\Message\CreatedQuoteRequestMessage $response */
        $response = $this->miraklApiClient->send($quoteMessageRequest);

        return $response && $response->getId() ? $response : null;
    }

    /**
     * Generate Mirakl Quote Lines Collection by Magento Quote for the specified Seller
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param int $sellerId
     * @return \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteLinesCollection(CartInterface $quote, int $sellerId): QuoteLineCollection
    {
        return $this->quoteDataUpdater->getQuoteLineCollection($quote, $sellerId);
    }

    /**
     * Fetch Last Quote from Quote Request
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $miraklQuoteRequest
     * @return \Mirakl\MMP\Front\Domain\Quote\Get\Quote|null
     */
    private function getLastQuoteFromRequest(?QuoteRequest $miraklQuoteRequest): ?Quote
    {
        $lastQuote = null;

        if ($miraklQuoteRequest) {
            $shopQuoteRequest = $miraklQuoteRequest->getShopQuoteRequests()->first();
            if ($shopQuoteRequest) {
                $lastQuote = $shopQuoteRequest->getQuotes()->first();
            }
        }

        return $lastQuote ?: null;
    }

    /**
     * Convert Mirakl Quote Request to Magento Quote
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $miraklQuoteRequest
     * @param string $miraklQuoteId
     * @return \Magento\Quote\Api\Data\CartInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function convertToMagentoQuote(?QuoteRequest $miraklQuoteRequest, string $miraklQuoteId): ?CartInterface
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create();
        $quote->setIsActive(true);
        $quote->setData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID, $miraklQuoteId);
        $quote->assignCustomer($this->getCustomer());
        $quote->setStoreId($this->customerSession->getCustomer()->getStoreId());
        $quote->setMiraklShippingZone($miraklQuoteRequest->getShippingZoneCode());
        $quote->setMiraklIsOfferInclTax($this->config->getOffersIncludeTax($quote->getStore()));
        $quote->setMiraklIsShippingInclTax($this->config->getShippingPricesIncludeTax($quote->getStore(), $quote));

        $shopQuoteRequest = $miraklQuoteRequest
            ->getShopQuoteRequests()
            ->first();

        $miraklQuote = $shopQuoteRequest
            ->getQuotes()
            ->first();

        if ($miraklQuote) {

            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $miraklQuoteItem */
            foreach ($miraklQuote->getLines() as $miraklQuoteItem) {
                $quoteItem = $this->addProductToQuote($quote, $miraklQuoteItem);
                $quoteItem->setMiraklShopId($shopQuoteRequest->getShopId());
            }

            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->collectTotals();
            $this->quoteRepository->save($quote);


        } else {
            $quote = null;
        }

        return $quote;
    }

    /**
     * Add Mirakl Quote Item to Magento Quote (convert to Quote Item)
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $miraklQuoteItem
     * @return bool|\Magento\Quote\Model\Quote\Item|string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addProductToQuote(CartInterface $quote, QuoteLine $miraklQuoteItem)
    {
        $product = $this->productRepository->get($miraklQuoteItem->getProductSku());
        $parentProduct = null;

        if ($product->getData(MiraklMcmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE)) {
            $parentProduct = $this->miraklMcmHelper->findMcmProductByVariantId(
                $product->getData(MiraklMcmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE), Configurable::TYPE_CODE
            );
        }

        if ($parentProduct) {
            $requestParams = [
                'qty' => $miraklQuoteItem->getQuantity(),
                'offer_id' => $miraklQuoteItem->getOfferId(),
                'product' => $parentProduct->getId(),
                'super_attribute' => []
            ];

            $productAttributeOptions = $parentProduct
                ->getTypeInstance()
                ->getConfigurableAttributesAsArray($parentProduct);
            foreach($productAttributeOptions as $option) {
                $requestParams['super_attribute'][$option['attribute_id']] = $product->getData($option['attribute_code']);
            }

            $request = $this->dataObjectFactory->create($requestParams);
            $quoteItem = $quote->addProduct($parentProduct, $request);

        } else {
            $requestParams = [
                'qty' => $miraklQuoteItem->getQuantity(),
                'offer_id' => $miraklQuoteItem->getOfferId(),
                'product' => $product->getId()
            ];

            $request = $this->dataObjectFactory->create($requestParams);
            $quoteItem = $quote->addProduct($product, $request);
        }
        $this->applyShippingFees($quoteItem, $miraklQuoteItem);

        $quoteItem->setCustomPrice($miraklQuoteItem->getUnitPrice());
        $quoteItem->setOriginalCustomPrice($miraklQuoteItem->getUnitPrice());

        $quoteItem->setMiraklBaseShippingFee($miraklQuoteItem->getShippingAmount());
        $quoteItem->setMiraklShippingFee($miraklQuoteItem->getShippingAmount());
        $quoteItem->setMiraklShippingType($miraklQuoteItem->getShippingTypeCode());
        $quoteItem->setMiraklOfferId($miraklQuoteItem->getOfferId());
        $quoteItem->setMiraklQuoteItemId($miraklQuoteItem->getId());
        $quoteItem->setData(MiraklQuoteAttributes::MIRAKL_QUOTE_ITEM_ID, $miraklQuoteItem->getId());

        return $quoteItem;
    }

    /**
     * Apply Shipping Fees
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $miraklQuoteItem
     */
    private function applyShippingFees(CartItemInterface $quoteItem, QuoteLine $miraklQuoteItem)
    {
        $this->miraklQuoteUpdater->setItemShippingFee(
            $quoteItem,
            $this->getMiraklShippingRateOffer($miraklQuoteItem)
        );
    }

    /**
     * Generate Mirakl Shipping Rate Offer
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $miraklQuoteItem
     * @return \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer
     */
    private function getMiraklShippingRateOffer(QuoteLine $miraklQuoteItem): ShippingRateOffer
    {
        /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer $shippingRateOffer */
        $shippingRateOffer = $this->shippingRateOfferFactory->create();
        $shippingRateOffer->setLineShippingPrice($miraklQuoteItem->getShippingAmount());

        return $shippingRateOffer;
    }

    /**
     * Get Current Customer ID
     *
     * @return int
     */
    private function getCustomerId(): int
    {
        return (int) $this->customerSession->getCustomerId();
    }

    /**
     * Get Current Customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|\Magento\Customer\Model\Customer
     */
    private function getCustomer(): ?CustomerInterface
    {
        try {
            $customer = $this->customerRepository->getById($this->getCustomerId());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $customer = null;
        }

        return $customer;
    }
}
