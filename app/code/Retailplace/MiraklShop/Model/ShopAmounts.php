<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model;

use Magento\Framework\Model\AbstractModel;
use Retailplace\MiraklShop\Api\Data\ShopAmountsInterface;

/**
 * Class ShopAmounts
 */
class ShopAmounts extends AbstractModel implements ShopAmountsInterface
{
    /**
     * Get Shop Total
     *
     * @return float
     */
    public function getShopTotal(): float
    {
        return (float) $this->getData(self::SHOP_TOTAL);
    }

    /**
     * Set Shop Total
     *
     * @param float $shopTotal
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setShopTotal(float $shopTotal): ShopAmountsInterface
    {
        return $this->setData(self::SHOP_TOTAL, $shopTotal);
    }

    /**
     * Get Shop Quotable Total
     *
     * @return float
     */
    public function getShopQuotableTotal(): float
    {
        return (float) $this->getData(self::SHOP_QUOTABLE_TOTAL);
    }

    /**
     * Set Shop Quotable Total
     *
     * @param float $shopQuotableTotal
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setShopQuotableTotal(float $shopQuotableTotal): ShopAmountsInterface
    {
        return $this->setData(self::SHOP_QUOTABLE_TOTAL, $shopQuotableTotal);
    }

    /**
     * Get Min Order Amount
     *
     * @return float
     */
    public function getMinOrderAmount(): float
    {
        return (float) $this->getData(self::MIN_ORDER_AMOUNT);
    }

    /**
     * Set Min Order Amount
     *
     * @param float $minOrderAmount
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinOrderAmount(float $minOrderAmount): ShopAmountsInterface
    {
        return $this->setData(self::MIN_ORDER_AMOUNT, $minOrderAmount);
    }

    /**
     * Get Min Order Amount Remaining
     *
     * @return float
     */
    public function getMinOrderAmountRemaining(): float
    {
        return (float) $this->getData(self::MIN_ORDER_AMOUNT_REMAINING);
    }

    /**
     * Set Min Order Amount Remaining
     *
     * @param float $minOrderAmountRemaining
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinOrderAmountRemaining(float $minOrderAmountRemaining): ShopAmountsInterface
    {
        return $this->setData(self::MIN_ORDER_AMOUNT_REMAINING, $minOrderAmountRemaining);
    }

    /**
     * Get Min Order Amount Percent
     *
     * @return int
     */
    public function getMinOrderAmountPercent(): int
    {
        return (int) $this->getData(self::MIN_ORDER_AMOUNT_PERCENT);
    }

    /**
     * Set Min Order Amount Percent
     *
     * @param int $minOrderAmountPercent
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinOrderAmountPercent(int $minOrderAmountPercent): ShopAmountsInterface
    {
        return $this->setData(self::MIN_ORDER_AMOUNT_PERCENT, $minOrderAmountPercent);
    }

    /**
     * Get Min Order Amount is Reached
     *
     * @return bool
     */
    public function getIsMinOrderAmountReached(): bool
    {
        return (bool) $this->getData(self::IS_MIN_ORDER_AMOUNT_REACHED);
    }

    /**
     * Set Min Order Amount is Reached
     *
     * @param bool $minOrderAmountIsReached
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setIsMinOrderAmountReached(bool $minOrderAmountIsReached): ShopAmountsInterface
    {
        return $this->setData(self::IS_MIN_ORDER_AMOUNT_REACHED, $minOrderAmountIsReached);
    }

    /**
     * Get Min Quote Amount
     *
     * @return float
     */
    public function getMinQuoteAmount(): float
    {
        return (float) $this->getData(self::MIN_QUOTE_AMOUNT);
    }

    /**
     * Set Min Quote Amount
     *
     * @param float $minQuoteAmount
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinQuoteAmount(float $minQuoteAmount): ShopAmountsInterface
    {
        return $this->setData(self::MIN_QUOTE_AMOUNT, $minQuoteAmount);
    }

    /**
     * Get Min Quote Amount Remaining
     *
     * @return float
     */
    public function getMinQuoteAmountRemaining(): float
    {
        return (float) $this->getData(self::MIN_QUOTE_AMOUNT_REMAINING);
    }

    /**
     * Set Min Quote Amount Remaining
     *
     * @param float $minQuoteAmountRemaining
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinQuoteAmountRemaining(float $minQuoteAmountRemaining): ShopAmountsInterface
    {
        return $this->setData(self::MIN_QUOTE_AMOUNT_REMAINING, $minQuoteAmountRemaining);
    }

    /**
     * Get Min Quote Amount Percent
     *
     * @return int
     */
    public function getMinQuoteAmountPercent(): int
    {
        return (int) $this->getData(self::MIN_QUOTE_AMOUNT_PERCENT);
    }

    /**
     * Set Min Quote Amount Percent
     *
     * @param int $minQuoteAmountPercent
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setMinQuoteAmountPercent(int $minQuoteAmountPercent): ShopAmountsInterface
    {
        return $this->setData(self::MIN_QUOTE_AMOUNT_PERCENT, $minQuoteAmountPercent);
    }

    /**
     * Get Min Quote Amount is Reached
     *
     * @return bool
     */
    public function getIsMinQuoteAmountReached(): bool
    {
        return (bool) $this->getData(self::IS_MIN_QUOTE_AMOUNT_REACHED);
    }

    /**
     * Set Min Quote Amount is Reached
     *
     * @param bool $minQuoteAmountIsReached
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setIsMinQuoteAmountReached(bool $minQuoteAmountIsReached): ShopAmountsInterface
    {
        return $this->setData(self::IS_MIN_QUOTE_AMOUNT_REACHED, $minQuoteAmountIsReached);
    }

    /**
     * Get Free Shipping Amount
     *
     * @return float
     */
    public function getFreeShippingAmount(): float
    {
        return (float) $this->getData(self::FREE_SHIPPING_AMOUNT);
    }

    /**
     * Set Free Shipping Amount
     *
     * @param float $freeShippingAmount
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setFreeShippingAmount(float $freeShippingAmount): ShopAmountsInterface
    {
        return $this->setData(self::FREE_SHIPPING_AMOUNT, $freeShippingAmount);
    }

    /**
     * Get Free Shipping Amount Remaining
     *
     * @return float
     */
    public function getFreeShippingAmountRemaining(): float
    {
        return (float) $this->getData(self::FREE_SHIPPING_AMOUNT_REMAINING);
    }

    /**
     * Set Free Shipping Amount Remaining
     *
     * @param float $freeShippingAmountRemaining
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setFreeShippingAmountRemaining(float $freeShippingAmountRemaining): ShopAmountsInterface
    {
        return $this->setData(self::FREE_SHIPPING_AMOUNT_REMAINING, $freeShippingAmountRemaining);
    }

    /**
     * Get Free Shipping Amount Percent
     *
     * @return int
     */
    public function getFreeShippingAmountPercent(): int
    {
        return (int) $this->getData(self::FREE_SHIPPING_AMOUNT_PERCENT);
    }

    /**
     * Set Free Shipping Amount Percent
     *
     * @param int $freeShippingAmountPercent
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setFreeShippingAmountPercent(int $freeShippingAmountPercent): ShopAmountsInterface
    {
        return $this->setData(self::FREE_SHIPPING_AMOUNT_PERCENT, $freeShippingAmountPercent);
    }

    /**
     * Get Free Shipping Amount is Reached
     *
     * @return bool
     */
    public function getIsFreeShippingAmountReached(): bool
    {
        return (bool) $this->getData(self::IS_FREE_SHIPPING_AMOUNT_REACHED);
    }

    /**
     * Set Free Shipping Amount is Reached
     *
     * @param bool $freeShippingAmountIsReached
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function setIsFreeShippingAmountReached(bool $freeShippingAmountIsReached): ShopAmountsInterface
    {
        return $this->setData(self::IS_FREE_SHIPPING_AMOUNT_REACHED, $freeShippingAmountIsReached);
    }
}
