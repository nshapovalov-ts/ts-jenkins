<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Plugin;

use Magento\Quote\Model\CouponManagement;
use Magento\Quote\Model\ResourceModel\Quote;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;
use Retailplace\MultiQuote\Model\QuoteHandlers;

/**
 * Class QuotesCouponManagement
 */
class QuotesCouponManagement
{
    /** @var \Retailplace\MultiQuote\Model\QuoteHandlers */
    private $quoteHandlers;

    /** @var \Retailplace\MultiQuote\Model\QuoteResource */
    private $quoteResourceModel;

    /**
     * QuotesCouponManagement Constructor
     *
     * @param \Retailplace\MultiQuote\Model\QuoteHandlers $quoteHandlers
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     */
    public function __construct(
        QuoteHandlers $quoteHandlers,
        Quote $quoteResourceModel
    ) {
        $this->quoteHandlers = $quoteHandlers;
        $this->quoteResourceModel = $quoteResourceModel;
    }

    /**
     * Sync Coupon Code with Related Quotes
     *
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param string $result
     * @param int $cartId
     * @param string $couponCode
     * @return string
     */
    public function afterSet(CouponManagement $subject, $result, $cartId, $couponCode)
    {
        $quote = $this->quoteHandlers->loadQuoteById($cartId, true);
        $parentId = $quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID);
        if ($parentId) {
            $parentQuote = $this->quoteHandlers->loadQuoteById($parentId);
            $parentQuote->setCouponCode($couponCode);
            $parentQuote->collectTotals();
            $parentQuote->getShippingAddress()->collectShippingRates();
            $this->quoteHandlers->saveQuote($parentQuote);
            $this->quoteResourceModel->removeChildQuotes($parentQuote->getId(), $cartId);
        } else {
            $this->quoteResourceModel->removeChildQuotes($cartId);
        }

        return $result;
    }

    /**
     * Sync Coupon Code with Related Quotes
     *
     * @param \Magento\Quote\Model\CouponManagement $subject
     * @param bool $result
     * @param int $cartId
     * @return bool
     */
    public function afterRemove(CouponManagement $subject, bool $result, $cartId): bool
    {
        $quote = $this->quoteHandlers->loadQuoteById($cartId, true);
        $parentId = $quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID);
        if ($parentId) {
            $parentQuote = $this->quoteHandlers->loadQuoteById($parentId);
            $parentQuote->setCouponCode('');
            $parentQuote->collectTotals();
            $parentQuote->getShippingAddress()->collectShippingRates();
            $this->quoteHandlers->saveQuote($parentQuote);
            $this->quoteResourceModel->removeChildQuotes($parentQuote->getId(), $cartId);
        } else {
            $this->quoteResourceModel->removeChildQuotes($cartId);
        }

        return $result;
    }
}
