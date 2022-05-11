<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */


namespace Amasty\Rules\Model\Rule\Action\Discount;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Action\Discount\Data;

/**
 * Amasty Rules calculation by action.
 *
 * @see \Amasty\Rules\Helper\Data::TYPE_EACH_N
 */
class EachnPerc extends Eachn
{
    /**
     * @param Rule $rule
     * @param AbstractItem $item
     *
     * @return Data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _calculate($rule, $item)
    {
        /** @var Data $discountData */
        $discountData = $this->discountFactory->create();
        $allItems = $this->getSortedItems(
            $item->getAddress(),
            $rule,
            $this->getSortOrder($rule, self::DEFAULT_SORT_ORDER)
        );

        $specialRule = $this->ruleResolver->getSpecialPromotions($rule);

        if ($specialRule->getUseFor() == self::USE_FOR_SAME_PRODUCT) {
            $allItems = $this->reduceItems($allItems, $rule);
        }

        $allItems = $this->skipEachN($allItems, $rule);
        $itemsId = $this->getItemsId($allItems);
        $rulePercent = min(100, $rule->getDiscountAmount());
        $_rulePct = $rulePercent / 100.0;

        /** @var AbstractItem $sortedItem */
        foreach ($allItems as $sortedItem) {
            if (in_array($item->getAmrulesId(), $itemsId) && $sortedItem->getAmrulesId() === $item->getAmrulesId()) {
                $itemOriginalPrice = $this->itemPrice->getItemOriginalPrice($item);
                $baseItemOriginalPrice = $this->itemPrice->getItemBaseOriginalPrice($item);
                $itemQty = $this->getArrayValueCount($itemsId, $item->getAmrulesId());

                $discountAmount = $this->itemPrice->getItemPrice($item) * $_rulePct;
                $baseDiscountAmount = $this->itemPrice->getItemBasePrice($item) * $_rulePct;

                $discountAmount = $this->itemPrice->resolveFinalPriceRevert(
                    $discountAmount,
                    $item
                );
                $baseDiscountAmount = $this->itemPrice->resolveBaseFinalPriceRevert(
                    $baseDiscountAmount,
                    $item
                );

                $discountData->setAmount($itemQty * $discountAmount);
                $discountData->setBaseAmount($itemQty * $baseDiscountAmount);
                $discountData->setOriginalAmount($itemQty * $itemOriginalPrice * $_rulePct);
                $discountData->setBaseOriginalAmount($itemQty * $baseItemOriginalPrice * $_rulePct);

                if (!$rule->getDiscountQty() || $rule->getDiscountQty() > $itemQty) {
                    $discountPercent = min(100, $item->getDiscountPercent() + $_rulePct*100);
                    $item->setDiscountPercent($discountPercent);
                }
            }
        }

        return $discountData;
    }
}
