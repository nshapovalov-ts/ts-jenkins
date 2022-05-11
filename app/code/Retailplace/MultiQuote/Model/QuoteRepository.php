<?php

/**
 * Retailplace_MultiQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MultiQuote\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\Quote\Api\Data\CartSearchResultsInterfaceFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteRepository as MagentoQuoteRepository;
use Magento\Quote\Model\QuoteRepository\LoadHandler as LoadHandler;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;
use Retailplace\OneSellerCheckout\Model\SellerQuoteManagement;

/**
 * Class QuoteRepository
 */
class QuoteRepository extends MagentoQuoteRepository
{
    /** @var \Magento\Quote\Model\QuoteRepository\LoadHandler */
    private $loadHandler;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /**
     * QuoteRepository Constructor
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection
     * @param \Magento\Quote\Api\Data\CartSearchResultsInterfaceFactory $searchResultsDataFactory
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Quote\Model\QuoteRepository\LoadHandler $loadHandler
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface|null $collectionProcessor
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory|null $quoteCollectionFactory
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory|null $cartFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        QuoteCollection $quoteCollection,
        CartSearchResultsInterfaceFactory $searchResultsDataFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CustomerSession $customerSession,
        LoadHandler $loadHandler,
        RequestInterface $request,
        CollectionProcessorInterface $collectionProcessor = null,
        QuoteCollectionFactory $quoteCollectionFactory = null,
        CartInterfaceFactory $cartFactory = null
    ) {
        parent::__construct(
            $quoteFactory,
            $storeManager,
            $quoteCollection,
            $searchResultsDataFactory,
            $extensionAttributesJoinProcessor,
            $collectionProcessor,
            $quoteCollectionFactory,
            $cartFactory
        );

        $this->customerSession = $customerSession;
        $this->loadHandler = $loadHandler;
        $this->request = $request;
    }

    /**
     * Get Quote from DB
     *
     * @param int $cartId
     * @param array $sharedStoreIds
     * @param bool $forceGetById
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($cartId, array $sharedStoreIds = [], bool $forceGetById = false)
    {
        if ($this->customerSession->isLoggedIn() && !$forceGetById) {
            try {
                $result = $this->getForCustomer($this->customerSession->getCustomerId());
            } catch (NoSuchEntityException $e) {
                if ($this->request->getParam(MiraklQuoteManagement::REQUEST_PARAM_MIRAKL_QUOTE_ID)
                    || $this->request->getParam(SellerQuoteManagement::REQUEST_PARAM_SELLER)) {
                    throw NoSuchEntityException::singleField('cartId', $cartId);
                } else {
                    $result = $this->getById((int) $cartId, $sharedStoreIds);
                }
            }
        } else {
            $result = $this->getById((int) $cartId, $sharedStoreIds);
        }

        return $result;
    }

    /**
     * Get Quote by ID
     *
     * @param int $cartId
     * @param array $sharedStoreIds
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getById(int $cartId, array $sharedStoreIds = [])
    {
        if (!isset($this->quotesById[$cartId])) {
            $quote = $this->loadQuote('loadByIdWithoutStore', 'cartId', $cartId, $sharedStoreIds);
            $this->loadHandler->load($quote);
            $this->quotesById[$cartId] = $quote;
        }

        return $this->quotesById[$cartId];
    }
}
