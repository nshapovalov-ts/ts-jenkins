<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\AmastyRuleOverride\Plugin\Magento\SalesRule\Model\Rule\Action\Discount;
use Amasty\Rules\Helper\Discount;
use Amasty\Rules\Model\RuleResolver;

class ByPercent
{
    /**
     * @var Discount
     */
    protected $rulesDiscountHelper;
    /**
     * @var RuleResolver
     */
    protected $ruleResolver;

    /**
     * ByPercent constructor.
     * @param Discount $rulesDiscountHelper
     * @param RuleResolver $ruleResolver
     */
    public function __construct(
        Discount $rulesDiscountHelper,
        RuleResolver $ruleResolver
    ) {
        $this->rulesDiscountHelper = $rulesDiscountHelper;
        $this->ruleResolver = $ruleResolver;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\ByPercent $subject
     * @param $discountData
     * @param $rule
     * @param $item
     * @param $qty
     * @return mixed
     * @throws \Exception
     */
    public function afterCalculate(
        \Magento\SalesRule\Model\Rule\Action\Discount\ByPercent $subject,
        $discountData,
        $rule,
        $item,
        $qty
    ) {
        $this->ruleResolver->getSpecialPromotions($rule);
        $this->rulesDiscountHelper->setDiscount(
            $rule,
            $discountData,
            $item->getQuote()->getStore(),
            $item->getId()
        );
        return $discountData;
    }
}
