<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */


namespace Amasty\Rules\Model\Rule\Action\Discount;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Action\Discount\Data;

/**
 * Amasty Rules calculation by action.
 *
 * @see \Amasty\Rules\Helper\Data::TYPE_XN_PERCENT
 */
class BuyxgetnPerc extends Buyxgety
{
    const RULE_VERSION = '1.0.0';

    /**
     * @param Rule $rule
     * @param AbstractItem $item
     * @param float $qty
     *
     * @return Data Data
     *
     * @throws LocalizedException
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function calculate($rule, $item, $qty)
    {
        $this->beforeCalculate($rule);
        $rulePercent = min(100, $rule->getDiscountAmount());
        $discountData = $this->_calculate($rule, $item, $rulePercent);
        $this->afterCalculate($discountData, $rule, $item);

        return $discountData;
    }

    /**
     * @param Rule $rule
     * @param AbstractItem $item
     * @param float $rulePercent
     *
     * @return Data Data
     *
     * @throws LocalizedException
     */
    protected function _calculate($rule, $item, $rulePercent)
    {
        /** @var Data $discountData */
        $discountData = $this->discountFactory->create();
        $specialRule = $this->ruleResolver->getSpecialPromotions($rule);

        // no conditions for Y elements
        if (!$specialRule->getPromoCats() && !$specialRule->getPromoSkus()) {
            return $discountData;
        }

        $rulePercent /= 100;
        $address = $item->getAddress();
        $triggerItems = $this->getTriggerElements($address, $rule);
        $realQty = $this->getTriggerElementQty($triggerItems);
        $maxQty = $this->getNQty($rule, $realQty);
        // find all allowed Y (discounted) elements and calculate total discount
        $passedItems = [];
        $lastId = 0;
        $currQty = 0;
        $allItems = $this->getSortedItems($address, $rule, self::DEFAULT_SORT_ORDER);
        $itemsId = $this->getItemsId($allItems);

        foreach ($allItems as $sortedItem) {
            if ($currQty >= $maxQty) {
                break;
            }

            // we always skip child items and calculate discounts inside parents
            if (!$this->canProcessItem($sortedItem, $triggerItems, $passedItems)) {
                continue;
            }
            // what should we do with bundles when we treat them as
            // separate items
            $passedItems[$sortedItem->getAmrulesId()] = $sortedItem->getAmrulesId();

            if (!$this->isDiscountedItem($rule, $sortedItem)) {
                continue;
            }

            $qty = $this->getItemQty($sortedItem);

            if (($qty == $currQty) && ($lastId == $item->getAmrulesId())) {
                continue;
            }

            $qty = min($maxQty - $currQty, $qty);
            $currQty += $qty;

            if (in_array($item->getAmrulesId(), $itemsId) && $sortedItem->getAmrulesId() === $item->getAmrulesId()) {
                $itemOriginalPrice = $this->itemPrice->getItemOriginalPrice($item);
                $baseItemOriginalPrice = $this->itemPrice->getItemBaseOriginalPrice($item);

                $discountAmount = $this->itemPrice->getItemPrice($item) * $rulePercent;
                $baseDiscountAmount = $this->itemPrice->getItemBasePrice($item) * $rulePercent;

                $discountAmount = $this->itemPrice->resolveFinalPriceRevert(
                    $discountAmount,
                    $item
                );
                $baseDiscountAmount = $this->itemPrice->resolveBaseFinalPriceRevert(
                    $baseDiscountAmount,
                    $item
                );

                $discountData->setAmount($qty * $discountAmount);
                $discountData->setBaseAmount($qty * $baseDiscountAmount);
                $discountData->setOriginalAmount($qty * $itemOriginalPrice * $rulePercent);
                $discountData->setBaseOriginalAmount($qty * $baseItemOriginalPrice * $rulePercent);

                if (!$rule->getDiscountQty() || $rule->getDiscountQty() > $qty) {
                    $discountPercent = min(100, $item->getDiscountPercent() + $rulePercent * 100);
                    $item->setDiscountPercent($discountPercent);
                }
            }
        }

        return $discountData;
    }
}
