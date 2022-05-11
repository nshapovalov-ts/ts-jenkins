<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\MMP\Common\Domain\UserType;
use Mirakl\MMP\Front\Domain\Quote\Get\Quote;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Mirakl\MMP\Front\Domain\Quote\Get\Quote as MiraklQuote;
use Mirakl\MMP\Front\Domain\Quote\Get\ShopQuoteRequest;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirakl\Mcm\Helper\Data as MiraklMcmHelper;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Magento\Framework\View\Element\BlockFactory;
use Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine;
use Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;
use Retailplace\MiraklQuote\Block\Html\Pager;
use Retailplace\MiraklQuote\Model\RequestCollectionWrapperFactory;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Mirakl\MMP\Front\Domain\Quote\Get\QuoteLineFactory;
use Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollectionFactory;
use Retailplace\MiraklFrontendDemo\Helper\Offer as OfferHelper;
use Mirakl\MMP\Front\Domain\Quote\Message\QuoteRequestMessageFactory;

/**
 * Class QuoteDataUpdater
 */
class QuoteDataUpdater
{
    /** @var string[] */
    public const SHIPPING_TITLE_MAPPING = [
        'subtotal' => 'Subtotal',
        'shipping' => 'Shipping Charges',
        'tax' => 'GST',
        'grand_total' => 'Order Total'
    ];

    /** @var string[] */
    public const STATUS_LABEL_MAPPING = [
        'TO_PROCESS' => 'Awaiting for Supplier Quote'
    ];

    /** @var string */
    public const SHOP_QUOTE_REQUEST_SHOP_NAME = 'shop_name';
    public const SHOP_QUOTE_REQUEST_URL = 'url';
    public const SHOP_QUOTE_REQUEST_ACCEPT_URL = 'accept_url';
    public const SHOP_QUOTE_REQUEST_DATE_CREATED = 'date_created';
    public const SHOP_QUOTE_REQUEST_DATE_FORMATTED = 'date_formatted';
    public const SHOP_QUOTE_REQUEST_EXPIRATION_DATE_FORMATTED = 'expiration_date_formatted';
    public const SHOP_QUOTE_REQUEST_STATE_LABEL = 'state_label';
    public const SHOP_QUOTE_REQUEST_STATE_LOWERCASE = 'state_lowercase';
    public const SHOP_QUOTE_REQUEST_CAN_SEND_MESSAGE = 'can_send_message';
    public const SHOP_QUOTE_REQUEST_TOTALS = 'totals';
    public const MESSAGE_DATE_CREATED_FORMATTED = 'date_created_formatted';
    public const MESSAGE_DIRECTION = 'direction';
    public const MESSAGE_DIRECTION_OUTBOUND = 'outbound';
    public const MESSAGE_DIRECTION_INBOUND = 'inbound';
    public const MIRAKL_QUOTE_MIRAKL_ORDERS = 'mirakl_orders';
    public const MIRAKL_QUOTE_REQUEST_STEP = 'step';
    public const QUOTE_LINE_OPTIONS = 'options';
    public const QUOTE_LINE_ROW_TOTAL = 'row_total';
    public const QUOTE_LINE_IMAGE_URL = 'image_url';
    public const QUOTE_LINE_PRODUCT_URL = 'product_url';
    public const QUOTE_LINE_LEADTIME_TO_SHIP = 'leadtime_to_ship';
    public const QUOTE_LINE_UNIT_PRICE_FORMATTED = 'unit_price_formatted';

    /** @var int */
    public const STEP_NEW = 1;
    public const STEP_GET_QUOTE = 2;
    public const STEP_CHOOSE_QUOTE = 3;
    public const STEP_ORDER_PLACED = 4;

    /** @var int */
    public const REQUESTS_PER_PAGE = 20;

    /** @var \Magento\Framework\UrlInterface */
    private $urlBuilder;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Magento\Framework\View\Element\BlockFactory */
    private $blockFactory;

    /** @var \Retailplace\MiraklQuote\Model\RequestCollectionWrapperFactory */
    private $requestCollectionWrapperFactory;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var \Magento\Catalog\Helper\Image */
    private $imageHelper;

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\Framework\Pricing\Helper\Data */
    private $priceHelper;

    /** @var \Mirakl\Mcm\Helper\Data */
    private $miraklMcmHelper;

    /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLineFactory */
    private $quoteLineFactory;

    /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollectionFactory */
    private $quoteLineCollectionFactory;

    /** @var \Retailplace\MiraklFrontendDemo\Helper\Offer */
    private $offerHelper;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;

    /** @var \Mirakl\MMP\Front\Domain\Quote\Message\QuoteRequestMessageFactory */
    private $quoteRequestMessageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param \Retailplace\MiraklQuote\Model\RequestCollectionWrapperFactory $requestCollectionWrapperFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Mirakl\Mcm\Helper\Data $miraklMcmHelper
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLineFactory $quoteLineFactory
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollectionFactory $quoteLineCollectionFactory
     * @param \Retailplace\MiraklFrontendDemo\Helper\Offer $offerHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Mirakl\MMP\Front\Domain\Quote\Message\QuoteRequestMessageFactory $quoteRequestMessageFactory
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ShopCollectionFactory $shopCollectionFactory,
        TimezoneInterface $timezone,
        BlockFactory $blockFactory,
        RequestCollectionWrapperFactory $requestCollectionWrapperFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ProductRepositoryInterface $productRepository,
        OfferRepositoryInterface $offerRepository,
        ImageHelper $imageHelper,
        PriceHelper $priceHelper,
        MiraklMcmHelper $miraklMcmHelper,
        QuoteLineFactory $quoteLineFactory,
        QuoteLineCollectionFactory $quoteLineCollectionFactory,
        OfferHelper $offerHelper,
        OrderRepositoryInterface $orderRepository,
        QuoteRequestMessageFactory $quoteRequestMessageFactory
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->timezone = $timezone;
        $this->blockFactory = $blockFactory;
        $this->requestCollectionWrapperFactory = $requestCollectionWrapperFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->offerRepository = $offerRepository;
        $this->priceHelper = $priceHelper;
        $this->miraklMcmHelper = $miraklMcmHelper;
        $this->quoteLineFactory = $quoteLineFactory;
        $this->quoteLineCollectionFactory = $quoteLineCollectionFactory;
        $this->offerHelper = $offerHelper;
        $this->orderRepository = $orderRepository;
        $this->quoteRequestMessageFactory = $quoteRequestMessageFactory;
    }

    /**
     * Add data to Quote Request Collection before render
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection|null $quoteRequestCollection
     */
    public function extendQuoteRequestCollection(?QuoteRequestCollection $quoteRequestCollection)
    {
        if ($quoteRequestCollection) {
            $shopsList = [];
            $quoteIds = [];
            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest $quoteRequest */
            foreach ($quoteRequestCollection as $quoteRequest) {
                /** @var \Mirakl\MMP\Front\Domain\Quote\Get\ShopQuoteRequest $shopQuoteRequest */
                foreach ($quoteRequest->getShopQuoteRequests() as $shopQuoteRequest) {
                    $shopQuoteRequest->setData(
                        self::SHOP_QUOTE_REQUEST_URL,
                        $this->urlBuilder->getUrl(
                            'quotes/actions/view/',
                            [
                                'id' => $shopQuoteRequest->getId()
                            ]
                        )
                    );

                    if ($shopQuoteRequest->getQuotes()->count()) {
                        $miraklQuote = $shopQuoteRequest->getQuotes()->last();
                        $quoteIds[] = $miraklQuote->getId();

                        if ($shopQuoteRequest->getState() != ShopQuoteRequest::ACCEPTED
                        && $miraklQuote->getState() != MiraklQuote::EXPIRED) {
                            $shopQuoteRequest->setData(
                                self::SHOP_QUOTE_REQUEST_ACCEPT_URL,
                                $this->urlBuilder->getUrl(
                                    'checkout',
                                    ['mirakl-quote' => $miraklQuote->getId()]
                                )
                            );

                            if ($miraklQuote->getExpirationDate()) {
                                $shopQuoteRequest->setData(
                                    self::SHOP_QUOTE_REQUEST_EXPIRATION_DATE_FORMATTED,
                                    $miraklQuote->getExpirationDate()->format('d/m/Y')
                                );
                            }
                            $shopQuoteRequest->setState($miraklQuote->getState());
                        }
                    }

                    $shopQuoteRequest->setData(self::SHOP_QUOTE_REQUEST_DATE_CREATED, $quoteRequest->getDateCreated());
                    $shopQuoteRequest->setData(self::SHOP_QUOTE_REQUEST_DATE_FORMATTED,
                        $this->timezone->date($quoteRequest->getDateCreated())->format('d/m/Y')
                    );
                    $shopQuoteRequest->setData(
                        self::SHOP_QUOTE_REQUEST_STATE_LABEL,
                        $this->getStatusLabel($shopQuoteRequest->getState())
                    );
                    $shopQuoteRequest->setData(
                        self::SHOP_QUOTE_REQUEST_STATE_LOWERCASE,
                        mb_strtolower($shopQuoteRequest->getState())
                    );
                    $shopsList[] = $shopQuoteRequest->getShopId();
                }
            }

            $this->addShopNames($quoteRequestCollection, $shopsList);
            $this->addRelatedOrders($quoteRequestCollection, $quoteIds);
            $this->processMessages($quoteRequestCollection);
        }
    }

    /**
     * Keep only last Quote in Quote Request
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection|null $quoteRequestCollection
     */
    public function cleanQuotesList(?QuoteRequestCollection $quoteRequestCollection)
    {
        if ($quoteRequestCollection) {
            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest $quoteRequest */
            foreach ($quoteRequestCollection as $quoteRequest) {
                foreach ($quoteRequest->getShopQuoteRequests() as $shopQuoteRequest) {
                    $lastQuote = $shopQuoteRequest->getQuotes()->last();
                    foreach ($shopQuoteRequest->getQuotes() as $key => $quote) {
                        if ($quote->getId() != $lastQuote->getId()) {
                            $shopQuoteRequest->getQuotes()->remove($key);
                        }
                    }
                }
            }
        }
    }

    /**
     * Update Messages Data
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection $quoteRequestCollection
     */
    private function processMessages(QuoteRequestCollection $quoteRequestCollection)
    {
        foreach ($quoteRequestCollection as $quoteRequest) {
            $this->addFirstMessage($quoteRequest);
            $customerFirstname = $quoteRequest->getCustomer()->getFirstname();
            $customerLastname = $quoteRequest->getCustomer()->getLastname();

            foreach ($quoteRequest->getShopQuoteRequests() as $shopQuoteRequest) {
                if ($this->isShowNewMessageForm($shopQuoteRequest)) {
                    $shopQuoteRequest->setData(self::SHOP_QUOTE_REQUEST_CAN_SEND_MESSAGE, true);
                }

                /** @var \Mirakl\MMP\Front\Domain\Quote\Message\QuoteRequestMessage $message */
                foreach ($shopQuoteRequest->getMessages() as $key => $message) {
                    $message->setData(self::MESSAGE_DATE_CREATED_FORMATTED,
                        $this->timezone->date($message->getDateCreated())->format('d/m/Y H:i')
                    );

                    if ($message->getFrom() == UserType::OPERATOR) {
                        $message->setFrom((string) __('Operator'));
                        $message->setTo($customerFirstname . ' ' . $customerLastname);
                        $message->setData(self::MESSAGE_DIRECTION, self::MESSAGE_DIRECTION_INBOUND);
                    } elseif ($message->getFrom() == UserType::SHOP) {
                        $message->setFrom($shopQuoteRequest->getData(self::SHOP_QUOTE_REQUEST_SHOP_NAME));
                        $message->setTo($customerFirstname . ' ' . $customerLastname);
                        $message->setData(self::MESSAGE_DIRECTION, self::MESSAGE_DIRECTION_INBOUND);
                    } elseif ($message->getFrom() == UserType::CUSTOMER) {
                        $message->setFrom($customerFirstname . ' ' . $customerLastname);
                        $message->setTo($shopQuoteRequest->getData(self::SHOP_QUOTE_REQUEST_SHOP_NAME));
                        $message->setData(self::MESSAGE_DIRECTION, self::MESSAGE_DIRECTION_OUTBOUND);
                    }
                }

                $shopQuoteRequest->setMessages(array_reverse($shopQuoteRequest->getMessages()->toArray()));
            }
        }
    }

    /**
     * Add Quote Request Initial Message to all Messages List
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest $quoteRequest
     */
    private function addFirstMessage(QuoteRequest $quoteRequest)
    {
        $bodyText = $quoteRequest->getInitialRequest()->getBody();
        $dateCreated = $quoteRequest->getDateCreated();

        $shopQuoteRequest = $quoteRequest->getShopQuoteRequests()->first();
        if ($shopQuoteRequest) {
            /** @var \Mirakl\MMP\Front\Domain\Quote\Message\QuoteRequestMessage $message */
            $message = $this->quoteRequestMessageFactory->create();
            $message->setBody($bodyText);
            $message->setDateCreated($dateCreated);
            $message->setFrom(UserType::CUSTOMER);
            $message->setTo(UserType::SHOP);

            $messagesList = $shopQuoteRequest->getMessages();
            $messagesArray = $messagesList->toArray();
            array_unshift($messagesArray, $message);
            $messagesList->setItems($messagesArray);
        }
    }

    /**
     * Check if Customer can send a message to Seller
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\ShopQuoteRequest $shopQuoteRequest
     * @return bool
     */
    private function isShowNewMessageForm(ShopQuoteRequest $shopQuoteRequest): bool
    {
        return in_array($shopQuoteRequest->getState(), [
            ShopQuoteRequest::IN_PROGRESS,
            ShopQuoteRequest::TO_PROCESS,
            Quote::ISSUED
        ]);
    }

    /**
     * Add Related Mirakl Orders to Quote
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection $quoteRequestCollection
     * @param array $quoteIds
     */
    private function addRelatedOrders(QuoteRequestCollection $quoteRequestCollection, array $quoteIds)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(MiraklQuoteAttributes::MIRAKL_ORDER_QUOTE_ID, $quoteIds, 'in')
            ->create();
        $ordersList = $this->orderRepository->getList($searchCriteria);
        $quotesData = [];
        foreach ($ordersList->getItems() as $order) {
            $quotesData[$order->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)] = $order->getEntityId();
        }

        foreach ($this->getAllQuotesFromRequestsCollection($quoteRequestCollection) as $miraklQuote) {
            $miraklOrders = [];

            if ($miraklQuote->getOrders()) {
                foreach ($miraklQuote->getOrders() as $miraklOrder) {
                    $orderId = $quotesData[$miraklQuote->getId()] ?? null;
                    if ($orderId) {
                        $miraklOrders[] = [
                            'id' => $miraklOrder->getId(),
                            'url' => $this->urlBuilder->getUrl(
                                'marketplace/order/view', [
                                    'order_id' => $orderId,
                                    'remote_id' => $miraklOrder->getId()
                                ]
                            )
                        ];
                    }
                }
            }

            $miraklQuote->setData(self::MIRAKL_QUOTE_MIRAKL_ORDERS, $miraklOrders);
        }
    }

    /**
     * Extract Quotes from Request Collection
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection $quoteRequestCollection
     * @return \Mirakl\MMP\Front\Domain\Quote\Get\Quote[]
     */
    private function getAllQuotesFromRequestsCollection(QuoteRequestCollection $quoteRequestCollection): array
    {
        $quotesArray = [];
        /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest $quoteRequest */
        foreach ($quoteRequestCollection as $quoteRequest) {
            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\ShopQuoteRequest $shopQuoteRequest */
            foreach ($quoteRequest->getShopQuoteRequests() as $shopQuoteRequest) {
                /** @var \Mirakl\MMP\Front\Domain\Quote\Get\Quote $miraklQuote */
                foreach ($shopQuoteRequest->getQuotes() as $miraklQuote) {
                    $quotesArray[] = $miraklQuote;
                }
            }
        }

        return $quotesArray;
    }

    /**
     * Add Seller Names to Quote Request Collection
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection $quoteRequestCollection
     * @param array $shopIds
     */
    private function addShopNames(QuoteRequestCollection $quoteRequestCollection, array $shopIds)
    {
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter('id', ['in' => $shopIds]);
        $shopsData = [];
        /** @var \Mirakl\Core\Model\Shop $shop */
        foreach ($shopCollection as $shop) {
            $shopsData[$shop->getId()] = $shop->getName();
        }

        /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest $quoteRequest */
        foreach ($quoteRequestCollection as $quoteRequest) {
            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\ShopQuoteRequest $shopQuoteRequest */
            foreach ($quoteRequest->getShopQuoteRequests() as $shopQuoteRequest) {
                $shopQuoteRequest->setData(
                    self::SHOP_QUOTE_REQUEST_SHOP_NAME,
                    $shopsData[$shopQuoteRequest->getShopId()] ?? null
                );
            }
        }
    }

    /**
     * Get Status label
     *
     * @param string $status
     * @return \Magento\Framework\Phrase
     */
    private function getStatusLabel(string $status): Phrase
    {
        if (!empty(self::STATUS_LABEL_MAPPING[$status])) {
            $statusLabel = __(self::STATUS_LABEL_MAPPING[$status]);
        } else {
            $statusLabel = __(
                ucwords(
                    mb_strtolower(
                        str_replace('_', ' ', $status)
                    )
                )
            );
        }

        return $statusLabel;
    }

    /**
     * Generate Pagination Block for Quote Requests
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteRequestCollection|null $quoteRequestCollection
     * @return string|null
     */
    public function getPagination(?QuoteRequestCollection $quoteRequestCollection): ?string
    {
        $pagination = null;
        if ($quoteRequestCollection) {
            /** @var \Retailplace\MiraklQuote\Model\RequestCollectionWrapper $requestCollectionWrapper */
            $requestCollectionWrapper = $this->requestCollectionWrapperFactory->create();
            $requestCollectionWrapper->setRequestCollection($quoteRequestCollection);

            /** @var \Retailplace\MiraklQuote\Block\Html\Pager $pager */
            $pager = $this->blockFactory->createBlock(Pager::class);
            $pager->setLimit(self::REQUESTS_PER_PAGE);
            $pager->setCollection($requestCollectionWrapper);
            $pager->setNameInLayout('retailplace_quotes_pager');
            $pager->setPath('quotes');

            $pagination = $pager->toHtml();
        }

        return $pagination;
    }

    /**
     * Get Pagination current Page
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        /** @var \Retailplace\MiraklQuote\Block\Html\Pager $pager */
        $pager = $this->blockFactory->createBlock(Pager::class);

        return (int) $pager->getCurrentPage();
    }

    /**
     * Set Quote Step
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $miraklQuoteRequest
     */
    public function setCurrentStep(?QuoteRequest $miraklQuoteRequest)
    {
        if ($miraklQuoteRequest) {
            if ($miraklQuoteRequest->getShopQuoteRequests()->count()
                && $miraklQuoteRequest->getShopQuoteRequests()->first()->getState() == ShopQuoteRequest::ACCEPTED) {
                $step = self::STEP_ORDER_PLACED;
            } elseif (count($miraklQuoteRequest->getShopQuoteRequests()->first()->getQuotes())) {
                $step = self::STEP_CHOOSE_QUOTE;
            } elseif (!count($miraklQuoteRequest->getShopQuoteRequests()->first()->getQuotes())) {
                $step = self::STEP_GET_QUOTE;
            } else {
                $step = self::STEP_NEW;
            }

            $miraklQuoteRequest->setData(self::MIRAKL_QUOTE_REQUEST_STEP, $step);
        }
    }

    /**
     * Add Totals to Mirakl Quote Request
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $miraklQuoteRequest
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     */
    public function setTotals(?QuoteRequest $miraklQuoteRequest, ?CartInterface $quote)
    {
        if ($miraklQuoteRequest && $quote) {
            $totalsArray = [];
            foreach ($quote->getShippingAddress()->getTotals() as $total) {
                $totalsArray[] = [
                    'title' => $this->getTotalTitle($total),
                    'code' => $total->getCode(),
                    'value' => $total->getShippingInclTax() ?: $total->getValue(),
                    'value_formatted' => $this->priceHelper->currency(
                    $total->getShippingInclTax() ?: $total->getValue(),
                    true,
                    false
                        )
                ];
            }
            $miraklQuoteRequest->getShopQuoteRequests()->first()->setData(self::SHOP_QUOTE_REQUEST_TOTALS, $totalsArray);
        }
    }

    /**
     * Add Products Data to Quote Request
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteRequest|null $miraklQuoteRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function extendWithProducts(?QuoteRequest $miraklQuoteRequest)
    {
        if ($miraklQuoteRequest) {
            $skuList = [];
            $offerIds = [];
            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $itemLine */
            foreach ($miraklQuoteRequest->getInitialRequest()->getLines() as $itemLine) {
                $skuList[] = $itemLine->getProductSku();
                $offerIds[] = $itemLine->getOfferId();
            }

            $productsArray = $this->getProductsList($skuList);
            $offersArray = $this->getOffersList($offerIds);

            /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $itemLine */
            foreach ($miraklQuoteRequest->getInitialRequest()->getLines() as $itemLine) {
                $product = $productsArray[$itemLine->getProductSku()] ?? null;
                if (!$product) {
                    throw new NoSuchEntityException(__(
                        'Product %1 not available anymore.',
                        $itemLine->getProductTitle()
                    ));
                }
                $parentProduct = $this->getParentMiraklProduct($product);
                if ($parentProduct) {
                    $childProduct = $product;
                } else {
                    $childProduct = null;
                    $parentProduct = $product;
                }
                $offer = $offersArray[$itemLine->getProductSku()] ?? null;
                $this->updateItemLine($itemLine, $parentProduct, $childProduct, $offer);
            }

            $quotes = $miraklQuoteRequest->getShopQuoteRequests()->first()->getQuotes();
            if ($quotes->first()) {
                foreach ($quotes->first()->getLines() as $itemLine) {
                    $product = $productsArray[$itemLine->getProductSku()] ?? null;
                    $parentProduct = $this->getParentMiraklProduct($product);
                    if ($parentProduct) {
                        $childProduct = $product;
                    } else {
                        $childProduct = null;
                        $parentProduct = $product;
                    }
                    $offer = $offersArray[$itemLine->getProductSku()] ?? null;
                    $this->updateItemLine($itemLine, $parentProduct, $childProduct, $offer);
                }
            }

        }
    }

    /**
     * Get Collection of Mirakl Quote Lines Items by Magento Quote
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param int|null $sellerId
     * @return \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteLineCollection(CartInterface $quote, ?int $sellerId = null): QuoteLineCollection
    {
        /** @var \Mirakl\MMP\Front\Domain\Collection\Quote\Get\QuoteLineCollection $quoteLinesCollection */
        $quoteLinesCollection = $this->quoteLineCollectionFactory->create();
        foreach ($quote->getItems() as $quoteItem) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            if (
                (!$sellerId && !$quoteItem->getParentItemId())
                || ($sellerId && $quoteItem->getData('mirakl_shop_id') == $sellerId)
            ) {
                $quoteLinesCollection->add($this->convertQuoteItemToQuoteLine($quoteItem));
            }
        }

        return $quoteLinesCollection;
    }

    /**
     * Convert Quote Item to Mirakl Quote Line Item
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertQuoteItemToQuoteLine(CartItemInterface $quoteItem): QuoteLine
    {
        $product = $quoteItem->getProduct();

        /** @var \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $itemLine */
        $itemLine = $this->quoteLineFactory->create();
        $itemLine->setQuantity((int) $quoteItem->getQty());
        $offer = $this->offerHelper->getBestOffer($product, (int) $quoteItem->getData('mirakl_shop_id'));

        $childProduct = null;
        if ($quoteItem->getOptionByCode('simple_product')) {
            $childProductId = $quoteItem->getOptionByCode('simple_product')->getProductId();
            $childProduct = $this->productRepository->getById($childProductId);
            $itemLine->setData(self::QUOTE_LINE_OPTIONS, $this->getSuperAttributeData($product, $childProduct));
        }
        $this->updateItemLine($itemLine, $product, $childProduct, $offer);

        return $itemLine;
    }

    /**
     * Map Totals Titles
     *
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return string
     */
    private function getTotalTitle(Total $total): string
    {
        return (string) (self::SHIPPING_TITLE_MAPPING[$total->getCode()] ?? $total->getTitle());
    }

    /**
     * Get Parent Product by Child
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    private function getParentMiraklProduct(ProductInterface $product): ?ProductInterface
    {
        $parentProduct = null;
        if ($product->getData(MiraklMcmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE)) {
            $parentProduct = $this->miraklMcmHelper->findMcmProductByVariantId(
                $product->getData(MiraklMcmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE), Configurable::TYPE_CODE
            );
        }

        return $parentProduct;
    }

    /**
     * Get Array of Super Attributes for Configurable Product by Child Product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $configurableProduct
     * @param \Magento\Catalog\Api\Data\ProductInterface $childProduct
     * @return array
     */
    private function getSuperAttributeData(ProductInterface $configurableProduct, ProductInterface $childProduct): array
    {
        $productAttributeOptions = $configurableProduct
            ->getTypeInstance(true)
            ->getConfigurableAttributes($configurableProduct);
        $additionalData = [];
        foreach ($productAttributeOptions as $option) {
            $optionValueId = $childProduct->getData($option->getProductAttribute()->getAttributeCode());
            foreach ($option->getOptions() as $optionValue) {
                if ($optionValue['value_index'] == $optionValueId) {
                    $label = $optionValue['label'];
                    $additionalData[] = $option->getLabel() . ': ' . $label;
                    break;
                }
            }
        }

        return $additionalData;
    }

    /**
     * Update Line Item with Product Data
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Get\QuoteLine $itemLine
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $product
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface|null $offer
     * @param \Magento\Catalog\Api\Data\ProductInterface|null $childProduct
     */
    private function updateItemLine(QuoteLine $itemLine, ?ProductInterface $product, ?ProductInterface $childProduct, ?OfferInterface $offer)
    {
        if ($product) {
            $productPrice = $product->getPrice();
            if (!$productPrice && $childProduct) {
                $productPrice = $childProduct->getPrice();
            }
            if (!$itemLine->getUnitPrice()) {
                $itemLine->setUnitPrice($productPrice);
            }

            $itemLine->setData(
                self::QUOTE_LINE_ROW_TOTAL,
                $this->priceHelper->currency($itemLine->getUnitPrice() * $itemLine->getQuantity(), true, false)
            );
            $itemLine->setData(
                self::QUOTE_LINE_UNIT_PRICE_FORMATTED,
                $this->priceHelper->currency($itemLine->getUnitPrice(), true, false)
            );

            if (!$itemLine->getProductTitle()) {
                $itemLine->setProductTitle($product->getName());
            }

            if ($childProduct) {
                $itemLine->setData(self::QUOTE_LINE_OPTIONS, $this->getSuperAttributeData($product, $childProduct));
            }

            $imageUrl = $this->imageHelper->init($product, 'cart_page_product_thumbnail')->getUrl();
            $itemLine->setData(self::QUOTE_LINE_IMAGE_URL, $imageUrl);
            $itemLine->setData(self::QUOTE_LINE_PRODUCT_URL, $this->getProductUrl(
                $product,
                $offer ? $offer->getShopId() : null
            ));
        }

        if ($offer) {
            $itemLine->setData(self::QUOTE_LINE_LEADTIME_TO_SHIP, $offer->getLeadtimeToShip());
        }
    }

    /**
     * Load list of Offers
     *
     * @param array $ids
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface[]
     */
    private function getOffersList(array $ids): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(OfferInterface::OFFER_ENTITY_ID, $ids, 'in')
            ->create();

        $offersArray = [];
        $offersList = $this->offerRepository->getList($searchCriteria);
        foreach ($offersList->getItems() as $offer) {
            $offersArray[$offer->getProductSku()] = $offer;
        }

        return $offersArray;
    }

    /**
     * Generate Product Url
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int|null $shopId
     * @return string
     */
    private function getProductUrl(ProductInterface $product, ?int $shopId = null): string
    {
        if ($shopId) {
            $productUrl = $this->urlBuilder->getUrl('seller') . $shopId .'/' . $product->getUrlKey() . '.html';
        } else {
            $productUrl = $product->getUrlModel()->getUrl($product);
        }

        return $productUrl;
    }

    /**
     * Load Products List by SKUs
     *
     * @param array $skus
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getProductsList(array $skus): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(ProductInterface::SKU, $skus, 'in')
            ->create();

        $productsArray = [];
        $productsList = $this->productRepository->getList($searchCriteria);
        /** @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product */
        foreach ($productsList->getItems() as $product) {
            $productsArray[$product->getSku()] = $product;
        }

        return $productsArray;
    }
}
