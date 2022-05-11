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
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteOfferCollection;
use Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteOfferCollectionFactory;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfo;
use Mirakl\MMP\Front\Domain\Quote\Create\CreateQuoteRequestFactory;
use Mirakl\MMP\Front\Domain\Quote\Create\QuoteOffer;
use Mirakl\MMP\Front\Domain\Quote\Create\QuoteOfferFactory;
use Mirakl\MMP\Front\Domain\Quote\Order\OrderFromQuote;
use Mirakl\MMP\Front\Request\Quote\CreateQuoteRequestsRequest;
use Mirakl\MMP\Front\Request\Quote\CreateQuoteRequestsRequestFactory;
use Mirakl\MMP\Front\Request\Quote\GetQuoteRequestsRequest;
use Mirakl\MMP\Front\Request\Quote\GetQuoteRequestsRequestFactory;
use Mirakl\MMP\Front\Request\Quote\Message\ShopQuoteRequestMessageRequest;
use Mirakl\MMP\Front\Request\Quote\Order\PlaceOrderFromQuoteRequest;
use Mirakl\MMP\FrontOperator\Domain\Quote\QuoteRequestCustomer;
use Mirakl\MMP\FrontOperator\Domain\Quote\QuoteRequestCustomerFactory;
use Mirakl\MMP\Front\Request\Quote\Order\PlaceOrderFromQuoteRequestFactory;
use Mirakl\MMP\Front\Domain\Quote\Order\OrderFromQuoteFactory;
use Mirakl\MMP\Front\Domain\Quote\Order\QuoteOrderLineFactory;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;
use Mirakl\Connector\Helper\Config as MiraklConfigHelper;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfoFactory;
use Mirakl\MMP\Front\Request\Quote\Message\ShopQuoteRequestMessageRequestFactory;

/**
 * Class QuoteRequestBuilder
 */
class QuoteRequestBuilder
{
    /** @var \Mirakl\MMP\Front\Request\Quote\GetQuoteRequestsRequestFactory */
    private $getQuoteRequestsRequestFactory;

    /** @var \Mirakl\MMP\Front\Domain\Quote\Create\QuoteOfferFactory */
    private $quoteOfferFactory;

    /** @var \Magento\Directory\Api\CountryInformationAcquirerInterface */
    private $countryInformationAcquirer;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    private $addressRepository;

    /** @var \Mirakl\MMP\FrontOperator\Domain\Quote\QuoteRequestCustomerFactory */
    private $quoteRequestCustomerFactory;

    /** @var \Mirakl\MMP\Front\Request\Quote\CreateQuoteRequestsRequestFactory */
    private $createQuoteRequestsRequestFactory;

    /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteOfferCollectionFactory */
    private $quoteOfferCollectionFactory;

    /** @var \Mirakl\MMP\Front\Domain\Quote\Create\CreateQuoteRequestFactory */
    private $quoteRequestFactory;

    /** @var \Mirakl\MMP\Front\Request\Quote\Order\PlaceOrderFromQuoteRequestFactory */
    private $placeOrderFromQuoteRequestFactory;

    /** @var \Mirakl\MMP\Front\Domain\Quote\Order\OrderFromQuoteFactory */
    private $orderFromQuoteFactory;

    /** @var \Mirakl\MMP\Front\Domain\Quote\Order\QuoteOrderLineFactory */
    private $quoteOrderLineFactory;

    /** @var \Mirakl\Connector\Helper\Config */
    private $miraklConfigHelper;

    /** @var \Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfoFactory */
    private $createOrderPaymentInfoFactory;

    /** @var \Mirakl\MMP\Front\Request\Quote\Message\ShopQuoteRequestMessageRequestFactory */
    private $shopQuoteRequestMessageRequestFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Mirakl\MMP\Front\Request\Quote\GetQuoteRequestsRequestFactory $getQuoteRequestsRequestFactory
     * @param \Mirakl\MMP\Front\Domain\Quote\Create\QuoteOfferFactory $quoteOfferFactory
     * @param \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirer
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Mirakl\MMP\FrontOperator\Domain\Quote\QuoteRequestCustomerFactory $quoteRequestCustomerFactory
     * @param \Mirakl\MMP\Front\Request\Quote\CreateQuoteRequestsRequestFactory $createQuoteRequestsRequestFactory
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteOfferCollectionFactory $quoteOfferCollectionFactory
     * @param \Mirakl\MMP\Front\Domain\Quote\Create\CreateQuoteRequestFactory $quoteRequestFactory
     * @param \Mirakl\MMP\Front\Request\Quote\Order\PlaceOrderFromQuoteRequestFactory $placeOrderFromQuoteRequestFactory
     * @param \Mirakl\MMP\Front\Domain\Quote\Order\OrderFromQuoteFactory $orderFromQuoteFactory
     * @param \Mirakl\MMP\Front\Domain\Quote\Order\QuoteOrderLineFactory $quoteOrderLineFactory
     * @param \Mirakl\Connector\Helper\Config $miraklConfigHelper
     * @param \Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfoFactory $createOrderPaymentInfoFactory
     * @param \Mirakl\MMP\Front\Request\Quote\Message\ShopQuoteRequestMessageRequestFactory $shopQuoteRequestMessageRequestFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        GetQuoteRequestsRequestFactory $getQuoteRequestsRequestFactory,
        QuoteOfferFactory $quoteOfferFactory,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        AddressRepositoryInterface $addressRepository,
        QuoteRequestCustomerFactory $quoteRequestCustomerFactory,
        CreateQuoteRequestsRequestFactory $createQuoteRequestsRequestFactory,
        QuoteOfferCollectionFactory $quoteOfferCollectionFactory,
        CreateQuoteRequestFactory $quoteRequestFactory,
        PlaceOrderFromQuoteRequestFactory $placeOrderFromQuoteRequestFactory,
        OrderFromQuoteFactory $orderFromQuoteFactory,
        QuoteOrderLineFactory $quoteOrderLineFactory,
        MiraklConfigHelper $miraklConfigHelper,
        CreateOrderPaymentInfoFactory $createOrderPaymentInfoFactory,
        ShopQuoteRequestMessageRequestFactory $shopQuoteRequestMessageRequestFactory,
        LoggerInterface $logger
    ) {
        $this->getQuoteRequestsRequestFactory = $getQuoteRequestsRequestFactory;
        $this->quoteOfferFactory = $quoteOfferFactory;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->addressRepository = $addressRepository;
        $this->quoteRequestCustomerFactory = $quoteRequestCustomerFactory;
        $this->createQuoteRequestsRequestFactory = $createQuoteRequestsRequestFactory;
        $this->quoteOfferCollectionFactory = $quoteOfferCollectionFactory;
        $this->quoteRequestFactory = $quoteRequestFactory;
        $this->placeOrderFromQuoteRequestFactory = $placeOrderFromQuoteRequestFactory;
        $this->orderFromQuoteFactory = $orderFromQuoteFactory;
        $this->quoteOrderLineFactory = $quoteOrderLineFactory;
        $this->miraklConfigHelper = $miraklConfigHelper;
        $this->createOrderPaymentInfoFactory = $createOrderPaymentInfoFactory;
        $this->shopQuoteRequestMessageRequestFactory = $shopQuoteRequestMessageRequestFactory;
        $this->logger = $logger;
    }

    /**
     * New Quote Request
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $quoteMessage
     * @param string $sellerId
     * @return \Mirakl\MMP\Front\Request\Quote\CreateQuoteRequestsRequest
     */
    public function createMiraklQuoteAddingRequest(CartInterface $quote, string $quoteMessage, string $sellerId): CreateQuoteRequestsRequest
    {
        /** @var \Mirakl\MMP\Front\Domain\Quote\Create\CreateQuoteRequest $quoteRequest */
        $quoteRequest = $this->quoteRequestFactory->create();
        $quoteRequest->setBody($quoteMessage);
        $quoteRequest->setShippingZoneCode($quote->getData('mirakl_shipping_zone'));
        $quoteRequest->setTaxesIncluded(true);
        $quoteRequest->setScored(true);
        $quoteRequest->setSubject('Quote Request');

        $quoteRequest->setCustomer($this->getQuoteRequestCustomer($quote->getCustomer()));
        $quoteRequest->setOffers($this->getQuoteOfferCollection($quote, $sellerId));

        /** @var \Mirakl\MMP\Front\Request\Quote\CreateQuoteRequestsRequest $request */
        $request = $this->createQuoteRequestsRequestFactory->create(['createQuoteRequests' => [$quoteRequest]]);

        return $request;
    }

    /**
     * Get Quote Requests List
     *
     * @param int|null $countPerPage
     * @param int|null $offset
     * @param string $sortBy
     * @param string $sortDir
     * @return \Mirakl\MMP\Front\Request\Quote\GetQuoteRequestsRequest
     */
    public function getQuoteRequestsRequest(
        ?int $countPerPage = null,
        ?int $offset = null,
        string $sortBy = 'date_created',
        string $sortDir = 'DESC'
    ): GetQuoteRequestsRequest {
        /** @var \Mirakl\MMP\Front\Request\Quote\GetQuoteRequestsRequest $getQuoteRequest */
        $request = $this->getQuoteRequestsRequestFactory->create();
        if ($countPerPage) {
            $request->setPaginate(true);
            $request->setOffset($offset);
            $request->setMax($countPerPage);
        }
        $request->setSortBy($sortBy);
        $request->setDir($sortDir);

        return $request;
    }

    /**
     * Generate Place Order from Quote Request
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Mirakl\MMP\Front\Request\Quote\Order\PlaceOrderFromQuoteRequest
     */
    public function getPlaceOrderFromQuoteRequest(OrderInterface $order): PlaceOrderFromQuoteRequest
    {
        /** @var \Mirakl\MMP\Front\Request\Quote\Order\PlaceOrderFromQuoteRequest $placeOrderFromQuoteRequest */
        $placeOrderFromQuoteRequest = $this->placeOrderFromQuoteRequestFactory->create([
            'quoteId' => $order->getData(MiraklQuoteAttributes::MIRAKL_ORDER_QUOTE_ID),
            'order' => $this->getOrderFromQuote($order)
        ]);

        return $placeOrderFromQuoteRequest;
    }

    /**
     * Get Quote Message Request
     *
     * @param string $shopQuoteRequestId
     * @param string $message
     * @param string $toType
     * @return \Mirakl\MMP\Front\Request\Quote\Message\ShopQuoteRequestMessageRequest
     */
    public function getQuoteMessageRequest(string $shopQuoteRequestId, string $message, string $toType = 'ALL'): ShopQuoteRequestMessageRequest
    {
        /** @var \Mirakl\MMP\Front\Request\Quote\Message\ShopQuoteRequestMessageRequest $quoteMessageRequest */
        $quoteMessageRequest = $this->shopQuoteRequestMessageRequestFactory->create([
            'shopQuoteRequestId' => $shopQuoteRequestId,
            'messageBody' => $message,
            'toType' => $toType
        ]);

        return $quoteMessageRequest;
    }

    /**
     * Get Mirakl Order from Quote Object
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Mirakl\MMP\Front\Domain\Quote\Order\OrderFromQuote
     */
    private function getOrderFromQuote(OrderInterface $order): OrderFromQuote
    {
        /** @var \Mirakl\MMP\Front\Domain\Quote\Order\OrderFromQuote $orderFromQuote */
        $orderFromQuote = $this->orderFromQuoteFactory->create();
        $orderFromQuote->setCommercialId($order->getIncrementId());
        $orderFromQuote->setPaymentWorkflow($this->miraklConfigHelper->getPaymentWorkflow());
        $orderFromQuote->setLines($this->getMiraklOrderLines($order->getItems()));
        $orderFromQuote->setScored(true);
        $paymentInfo = $this->getPaymentInfo($order->getPayment());
        if ($paymentInfo) {
            $orderFromQuote->setPaymentInfo($paymentInfo);
        }

        return $orderFromQuote;
    }

    /**
     * Get Mirakl Payment Object
     *
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface|null $payment
     * @return \Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfo|null
     */
    private function getPaymentInfo(?OrderPaymentInterface $payment): ?CreateOrderPaymentInfo
    {
        $paymentInfo = null;
        if ($payment) {
            /** @var \Mirakl\MMP\Front\Domain\Order\Create\CreateOrderPaymentInfo $paymentInfo */
            $paymentInfo = $this->createOrderPaymentInfoFactory->create();
            $paymentInfo->setPaymentType($payment->getMethod());
        }

        return $paymentInfo;
    }

    /**
     * Get Mirakl Order Lines
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface[] $orderItems
     * @return \Mirakl\MMP\Front\Domain\Quote\Order\QuoteOrderLine[]
     */
    private function getMiraklOrderLines(array $orderItems): array
    {
        $lines = [];
        foreach ($orderItems as $orderItem) {
            if ($orderItem->getParentItemId()) {
                continue;
            }

            /** @var \Mirakl\MMP\Front\Domain\Quote\Order\QuoteOrderLine $miraklOrderItem */
            $miraklOrderItem = $this->quoteOrderLineFactory->create();
            $miraklOrderItem->setQuoteLineId($orderItem->getData(MiraklQuoteAttributes::MIRAKL_ORDER_ITEM_ID));
            $lines[] = $miraklOrderItem;
        }

        return $lines;
    }

    /**
     * Convert Magento Customer to Quote Request Customer
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return \Mirakl\MMP\FrontOperator\Domain\Quote\QuoteRequestCustomer
     */
    private function getQuoteRequestCustomer(CustomerInterface $customer): QuoteRequestCustomer
    {
        /** @var \Mirakl\MMP\FrontOperator\Domain\Quote\QuoteRequestCustomer $quoteRequestCustomer */
        $quoteRequestCustomer = $this->quoteRequestCustomerFactory->create();

        try {
            $billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
            $street = $billingAddress->getStreet() ?: [];
            $countryInformation = $this->countryInformationAcquirer->getCountryInfo($billingAddress->getCountryId());
            $quoteRequestCustomer->setBillingAddress([
                'city' => $billingAddress->getCity(),
                'company' => $billingAddress->getCompany(),
                'country' => $countryInformation->getFullNameEnglish(),
                'country_iso_code' => $countryInformation->getThreeLetterAbbreviation(),
                'firstname' => $billingAddress->getFirstname(),
                'lastname' => $billingAddress->getLastname(),
                'phone' => $billingAddress->getTelephone(),
                'street_1' => implode(', ', $street),
                'zip_code' => $billingAddress->getPostcode()
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        try {
            $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
            $street = $shippingAddress->getStreet() ?: [];
            $countryInformation = $this->countryInformationAcquirer->getCountryInfo($shippingAddress->getCountryId());
            $quoteRequestCustomer->setShippingAddress([
                'city' => $shippingAddress->getCity(),
                'company' => $shippingAddress->getCompany(),
                'country' => $countryInformation->getFullNameEnglish(),
                'country_iso_code' => $countryInformation->getThreeLetterAbbreviation(),
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'phone' => $shippingAddress->getTelephone(),
                'street_1' => implode(', ', $street),
                'zip_code' => $shippingAddress->getPostcode()
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $quoteRequestCustomer->setEmail($customer->getEmail());
        $quoteRequestCustomer->setFirstname($customer->getFirstname());
        $quoteRequestCustomer->setLastname($customer->getLastname());
        $quoteRequestCustomer->setCustomerId((string) $customer->getId());

        return $quoteRequestCustomer;
    }

    /**
     * Generate Quote Offer Collection
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param string $sellerId
     * @return \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteOfferCollection
     */
    private function getQuoteOfferCollection(CartInterface $quote, string $sellerId): QuoteOfferCollection
    {
        /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Create\QuoteOfferCollection $quoteOfferCollection */
        $quoteOfferCollection = $this->quoteOfferCollectionFactory->create();
        foreach ($quote->getItems() as $quoteItem) {
            if ($quoteItem->getData('mirakl_shop_id') == $sellerId) {
                $offerItem = $this->convertQuoteItemToQuoteOffer($quoteItem);
                $offerItem->setCurrencyIsoCode($quote->getCurrency()->getQuoteCurrencyCode());
                $quoteOfferCollection->add($offerItem);
            }
        }

        return $quoteOfferCollection;
    }

    /**
     * Convert Magento Quote Item to Quote Offer
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return \Mirakl\MMP\Front\Domain\Quote\Create\QuoteOffer
     */
    private function convertQuoteItemToQuoteOffer(CartItemInterface $item): QuoteOffer
    {
        /** @var \Mirakl\MMP\Front\Domain\Quote\Create\QuoteOffer $quoteOffer */
        $quoteOffer = $this->quoteOfferFactory->create();
        $quoteOffer->setQuantity((int) $item->getQty());
        $quoteOffer->setOfferId($item->getData('mirakl_offer_id'));

        return $quoteOffer;
    }
}
