<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\CartInterface;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;
use Magento\Quote\Api\Data\CartInterfaceFactory as QuoteFactory;

/**
 * Class SellerQuoteManagement
 */
class SellerQuoteManagement
{
    /** @var string */
    public const REQUEST_PARAM_SELLER = 'quote-seller';

    /** @var \Magento\Quote\Api\Data\CartInterfaceFactory */
    private $quoteFactory;

    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /**
     * QuoteManagement Constructor
     *
     * @param \Magento\Quote\Api\Data\CartInterfaceFactory $quoteFactory
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        RequestInterface $request
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->request = $request;
    }

    /**
     * Check if Quote is for Single Seller
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function isQuoteSingleSeller(CartInterface $quote): bool
    {
        return (bool) $quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID);
    }

    /**
     * Clone Quote and Address
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function cloneQuote(CartInterface $quote): CartInterface
    {
        /** @var \Magento\Quote\Model\Quote $clonedQuote */
        $clonedQuote = $this->quoteFactory->create();
        $clonedQuote->merge($quote);
        $clonedQuote->setStoreId($quote->getStoreId());
        $clonedQuote->setCustomer($quote->getCustomer());
        $clonedQuote->setIsActive(true);
        $clonedQuote->setData(OneSellerQuoteAttributes::QUOTE_SELLER_ID, $this->request->getParam(self::REQUEST_PARAM_SELLER));

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setQuote($clonedQuote);
        $shippingAddress->setId(null);
        $clonedQuote->setShippingAddress($shippingAddress);

        $clonedQuote->setData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID, $quote->getId());
        $clonedQuote->collectTotals();

        return $clonedQuote;
    }

    /**
     * Create Quote and Set Data from Array
     *
     * @param array $data
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     */
    public function createQuoteFromData(array $data): CartInterface
    {
        $quote = $this->quoteFactory->create();
        $quote->setData($data);
        $quote->setOrigData();

        return $quote;
    }

    /**
     * Remove Quote Items Except of Single Seller
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param int $sellerId
     * @return void
     */
    public function filterItemsByShop(CartInterface $quote, int $sellerId)
    {
        foreach ($quote->getItems() as $quoteItem) {
            if ($quoteItem->getData('mirakl_shop_id') != $sellerId) {
                $quote->deleteItem($quoteItem);
            }
        }
    }

    /**
     * Get Seller ID Param from Request
     *
     * @return int
     */
    public function getSellerIdFromRequest(): int
    {
        return (int) $this->request->getParam(self::REQUEST_PARAM_SELLER);
    }
}
