<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */

declare(strict_types=1);

namespace Amasty\Rules\Model\Rule;

use Magento\Quote\Model\Quote\Item\AbstractItem;

class ItemCalculationPrice
{
    const DEFAULT_PRICE = 0;
    const DISCOUNTED_PRICE = 1;
    const ORIGIN_PRICE = 2;
    const ORIGIN_WITH_REVERT = 3;

    /**
     * Current rule price selector
     *
     * @var int
     */
    private $priceSelector = self::DEFAULT_PRICE;

    /**
     * @var \Magento\SalesRule\Model\Validator
     */
    private $validator;

    public function __construct(
        \Magento\SalesRule\Model\Validator $validator
    ) {
        $this->validator = $validator;
    }

    /**
     * @param AbstractItem $item
     *
     * @return float
     */
    public function getItemPrice(AbstractItem $item): float
    {
        $price = $this->validator->getItemPrice($item);
        switch ($this->getPriceSelector()) {
            case self::DISCOUNTED_PRICE:
                $price -= $item->getDiscountAmount() / $item->getQty();
                break;
            case self::ORIGIN_PRICE:
            case self::ORIGIN_WITH_REVERT:
                $price = $item->getOriginalPrice();
                break;
        }

        return (float)$price;
    }

    /**
     * @param AbstractItem $item
     *
     * @return float
     */
    public function getItemBasePrice(AbstractItem $item): float
    {
        $price = $this->validator->getItemBasePrice($item);
        switch ($this->getPriceSelector()) {
            case self::DISCOUNTED_PRICE:
                $price -= $item->getBaseDiscountAmount() / $item->getQty();
                break;
            case self::ORIGIN_PRICE:
            case self::ORIGIN_WITH_REVERT:
                $price = $item->getBaseOriginalPrice();
                break;
        }

        return (float)$price;
    }

    /**
     * Return item original price
     *
     * @param AbstractItem $item
     *
     * @return float
     */
    public function getItemOriginalPrice(AbstractItem $item): float
    {
        $price = $this->validator->getItemOriginalPrice($item);
        switch ($this->getPriceSelector()) {
            case self::DISCOUNTED_PRICE:
                $price -= $item->getDiscountAmount() / $item->getQty();
                break;
            case self::ORIGIN_PRICE:
            case self::ORIGIN_WITH_REVERT:
                $price = $item->getProduct()->getPrice();
                break;
        }

        return (float)$price;
    }

    /**
     * Return item original price
     *
     * @param AbstractItem $item
     *
     * @return float
     */
    public function getItemBaseOriginalPrice(AbstractItem $item): float
    {
        $price = $this->validator->getItemBaseOriginalPrice($item);
        switch ($this->getPriceSelector()) {
            case self::DISCOUNTED_PRICE:
                $price -= $item->getBaseDiscountAmount() / $item->getQty();
                break;
            case self::ORIGIN_PRICE:
            case self::ORIGIN_WITH_REVERT:
                $price = $item->getBaseOriginalPrice();
                break;
        }

        return (float)$price;
    }

    /**
     * @param AbstractItem $item
     *
     * @return float
     */
    public function getFinalPriceRevert(AbstractItem $item): float
    {
        return (float)$item->getOriginalPrice() - $this->validator->getItemPrice($item);
    }

    /**
     * @param AbstractItem $item
     *
     * @return float
     */
    public function getBaseFinalPriceRevert(AbstractItem $item): float
    {
        return (float)$item->getBaseOriginalPrice() - $this->validator->getItemBasePrice($item);
    }

    /**
     * @param float $discount
     * @param AbstractItem $item
     *
     * @return float
     */
    public function resolveFinalPriceRevert(float $discount, AbstractItem $item): float
    {
        if ($this->getPriceSelector() !== self::ORIGIN_WITH_REVERT) {
            return $discount;
        }

        return max($discount - $this->getFinalPriceRevert($item), .0);
    }

    /**
     * @param float $discount
     * @param AbstractItem $item
     *
     * @return float
     */
    public function resolveBaseFinalPriceRevert(float $discount, AbstractItem $item): float
    {
        if ($this->getPriceSelector() !== self::ORIGIN_WITH_REVERT) {
            return $discount;
        }

        return max($discount - $this->getBaseFinalPriceRevert($item), .0);
    }

    /**
     * @return int
     */
    public function getPriceSelector(): int
    {
        return $this->priceSelector;
    }

    /**
     * @param int $priceSelector
     */
    public function setPriceSelector(int $priceSelector): void
    {
        $this->priceSelector = $priceSelector;
    }
}
