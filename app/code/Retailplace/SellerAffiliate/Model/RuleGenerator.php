<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\Rule;

/**
 * RuleGenerator Class
 */
class RuleGenerator
{
    /** @var int */
    const CART_PRICE_RULE_START_ID = 1000000;

    /** @var TsConnectFirstOrderDiscount */
    private $tsConnectFirstOrderDiscount;

    /** @var TsConnectFreeShipping */
    private $tsConnectFreeShipping;

    /**
     * RuleGenerator Constructor
     *
     * @param TsConnectFirstOrderDiscount $tsConnectFirstOrderDiscount
     * @param TsConnectFreeShipping $tsConnectFreeShipping
     */
    public function __construct(
        TsConnectFirstOrderDiscount $tsConnectFirstOrderDiscount,
        TsConnectFreeShipping $tsConnectFreeShipping
    ) {
        $this->tsConnectFirstOrderDiscount = $tsConnectFirstOrderDiscount;
        $this->tsConnectFreeShipping = $tsConnectFreeShipping;
    }

    /**
     * @param CartInterface $quote
     * @return Rule[]
     */
    public function generateRules(CartInterface $quote): array
    {
        $ruleId = self::CART_PRICE_RULE_START_ID;
        $rules = [];
        if ($this->tsConnectFirstOrderDiscount->isPromoRulesEnable()) {
            $promoRules = $this->tsConnectFirstOrderDiscount->getRulesData($quote);

            foreach ($promoRules as $promoRule) {
                $promoRule->setId(++$ruleId);
                $rules[] = $promoRule;
            }
        }

        if ($this->tsConnectFreeShipping->isShippingRulesEnable()) {
            $shippingRules = $this->tsConnectFreeShipping->getRulesData($quote);
            foreach ($shippingRules as $shippingRule) {
                $shippingRule->setId(++$ruleId);
                $rules[] = $shippingRule;
            }
        }

        return $rules;
    }
}
