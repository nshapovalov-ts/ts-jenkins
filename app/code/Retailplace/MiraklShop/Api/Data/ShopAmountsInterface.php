<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Api\Data;

/**
 * Interface ShopAmountsInterface
 */
interface ShopAmountsInterface
{
    /** @var string */
    public const SHOP_TOTAL = 'shop_total';
    public const SHOP_QUOTABLE_TOTAL = 'shop_quotable_total';

    /** @var string */
    public const MIN_ORDER_AMOUNT = 'min_order_amount';
    public const MIN_ORDER_AMOUNT_REMAINING = 'min_order_amount_remaining';
    public const MIN_ORDER_AMOUNT_PERCENT = 'min_order_amount_percent';
    public const IS_MIN_ORDER_AMOUNT_REACHED = 'is_min_order_amount_reached';

    /** @var string */
    public const MIN_QUOTE_AMOUNT = 'min_quote_amount';
    public const MIN_QUOTE_AMOUNT_REMAINING = 'min_quote_amount_remaining';
    public const MIN_QUOTE_AMOUNT_PERCENT = 'min_quote_amount_percent';
    public const IS_MIN_QUOTE_AMOUNT_REACHED = 'is_min_quote_amount_reached';

    /** @var string */
    public const FREE_SHIPPING_AMOUNT = 'free_shipping_amount';
    public const FREE_SHIPPING_AMOUNT_REMAINING = 'free_shipping_amount_remaining';
    public const FREE_SHIPPING_AMOUNT_PERCENT = 'free_shipping_amount_percent';
    public const IS_FREE_SHIPPING_AMOUNT_REACHED = 'is_free_shipping_amount_reached';

    /**
     * Get Shop Total
     *
     * @return float
     */
    public function getShopTotal(): float;

    /**
     * Set Shop Total
     *
     * @param float $shopTotal
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setShopTotal(float $shopTotal): ShopAmountsInterface;

    /**
     * Get Shop Quotable Total
     *
     * @return float
     */
    public function getShopQuotableTotal(): float;

    /**
     * Set Shop Quotable Total
     *
     * @param float $shopQuotableTotal
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setShopQuotableTotal(float $shopQuotableTotal): ShopAmountsInterface;

    /**
     * Get Min Order Amount
     *
     * @return float
     */
    public function getMinOrderAmount(): float;

    /**
     * Set Min Order Amount
     *
     * @param float $minOrderAmount
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinOrderAmount(float $minOrderAmount): ShopAmountsInterface;

    /**
     * Get Min Order Amount Remaining
     *
     * @return float
     */
    public function getMinOrderAmountRemaining(): float;

    /**
     * Set Min Order Amount Remaining
     *
     * @param float $minOrderAmountRemaining
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinOrderAmountRemaining(float $minOrderAmountRemaining): ShopAmountsInterface;

    /**
     * Get Min Order Amount Remaining Percent
     *
     * @return int
     */
    public function getMinOrderAmountPercent(): int;

    /**
     * Set Min Order Amount Percent
     *
     * @param int $minOrderAmountPercent
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinOrderAmountPercent(int $minOrderAmountPercent): ShopAmountsInterface;

    /**
     * Get Min Order Amount is Reached
     *
     * @return bool
     */
    public function getIsMinOrderAmountReached(): bool;

    /**
     * Set Min Order Amount is Reached
     *
     * @param bool $minOrderAmountIsReached
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setIsMinOrderAmountReached(bool $minOrderAmountIsReached): ShopAmountsInterface;

    /**
     * Get Min Quote Amount
     *
     * @return float
     */
    public function getMinQuoteAmount(): float;

    /**
     * Set Min Quote Amount
     *
     * @param float $minQuoteAmount
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinQuoteAmount(float $minQuoteAmount): ShopAmountsInterface;

    /**
     * Get Min Quote Amount Remaining
     *
     * @return float
     */
    public function getMinQuoteAmountRemaining(): float;

    /**
     * Set Min Quote Amount Remaining
     *
     * @param float $minQuoteAmountRemaining
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinQuoteAmountRemaining(float $minQuoteAmountRemaining): ShopAmountsInterface;

    /**
     * Get Min Quote Amount Percent
     *
     * @return int
     */
    public function getMinQuoteAmountPercent(): int;

    /**
     * Set Min Quote Amount Percent
     *
     * @param int $minQuoteAmountPercent
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinQuoteAmountPercent(int $minQuoteAmountPercent): ShopAmountsInterface;

    /**
     * Get Min Quote Amount is Reached
     *
     * @return bool
     */
    public function getIsMinQuoteAmountReached(): bool;

    /**
     * Set Min Quote Amount is Reached
     *
     * @param bool $minQuoteAmountIsReached
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setIsMinQuoteAmountReached(bool $minQuoteAmountIsReached): ShopAmountsInterface;

    /**
     * Get Free Shipping Amount
     *
     * @return float
     */
    public function getFreeShippingAmount(): float;

    /**
     * Set Free Shipping Amount
     *
     * @param float $freeShippingAmount
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setFreeShippingAmount(float $freeShippingAmount): ShopAmountsInterface;

    /**
     * Get Free Shipping Amount Remaining
     *
     * @return float
     */
    public function getFreeShippingAmountRemaining(): float;

    /**
     * Set Free Shipping Amount Remaining
     *
     * @param float $freeShippingAmountRemaining
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setFreeShippingAmountRemaining(float $freeShippingAmountRemaining): ShopAmountsInterface;

    /**
     * Get Free Shipping Amount Percent
     *
     * @return int
     */
    public function getFreeShippingAmountPercent(): int;

    /**
     * Set Free Shipping Amount Percent
     *
     * @param int $freeShippingAmountPercent
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setFreeShippingAmountPercent(int $freeShippingAmountPercent): ShopAmountsInterface;

    /**
     * Get Free Shipping Amount is Reached
     *
     * @return bool
     */
    public function getIsFreeShippingAmountReached(): bool;

    /**
     * Set Free Shipping Amount is Reached
     *
     * @param bool $freeShippingAmountIsReached
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setIsFreeShippingAmountReached(bool $freeShippingAmountIsReached): ShopAmountsInterface;
}
