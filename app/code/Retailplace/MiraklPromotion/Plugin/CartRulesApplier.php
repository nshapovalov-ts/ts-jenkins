<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Plugin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\SalesRule\Api\Data\DiscountDataInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleDiscountInterfaceFactory;
use Magento\SalesRule\Model\Quote\ChildrenValidationLocator;
use Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory;
use Magento\SalesRule\Model\Rule\Action\Discount\DataFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RulesApplier;
use Magento\SalesRule\Model\Utility;
use Retailplace\MiraklPromotion\Model\PromotionManagement;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;

/**
 * Cart Rules Applier
 */
class CartRulesApplier extends \Magento\SalesRule\Model\RulesApplier
{
    /** @var \Magento\SalesRule\Model\RuleFactory */
    private $ruleFactory;

    /** @var \Magento\Framework\Serialize\SerializerInterface */
    private $serializer;

    /**
     * CartRulesApplier Constructor
     *
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\SalesRule\Model\Utility $utility
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\SalesRule\Model\Quote\ChildrenValidationLocator|null $childrenValidationLocator
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory|null $discountDataFactory
     * @param \Magento\SalesRule\Api\Data\RuleDiscountInterfaceFactory|null $discountInterfaceFactory
     * @param \Magento\SalesRule\Api\Data\DiscountDataInterfaceFactory|null $discountDataInterfaceFactory
     */
    public function __construct(
        CalculatorFactory $calculatorFactory,
        ManagerInterface $eventManager,
        Utility $utility,
        RuleFactory $ruleFactory,
        SerializerInterface $serializer,
        ChildrenValidationLocator $childrenValidationLocator = null,
        DataFactory $discountDataFactory = null,
        RuleDiscountInterfaceFactory $discountInterfaceFactory = null,
        DiscountDataInterfaceFactory $discountDataInterfaceFactory = null
    ) {
        parent::__construct($calculatorFactory, $eventManager, $utility, $childrenValidationLocator, $discountDataFactory,
            $discountInterfaceFactory, $discountDataInterfaceFactory);

        $this->ruleFactory = $ruleFactory;
        $this->serializer = $serializer;
    }

    /**
     * Add Custom Cart Price Rule (with Promotion) before all the rest
     *
     * @param \Magento\SalesRule\Model\RulesApplier $subject
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $rules
     * @param $skipValidation
     * @param $couponCode
     * @return array
     */
    public function beforeApplyRules(RulesApplier $subject, Item $item, RuleCollection $rules, $skipValidation, $couponCode)
    {
        $deducedAmount = $item->getData(PromotionManagement::QUOTE_MIRAKL_PROMOTION_DEDUCED_AMOUNT);
        if ($deducedAmount) {
            $promotionsData = $this->serializer->unserialize($item->getData(PromotionManagement::QUOTE_MIRAKL_PROMOTION_DATA));
            $descriptions = [];
            if ($promotionsData) {
                foreach ($promotionsData as $promotion) {
                    $descriptions[] = $promotion['configuration']['internal_description'];
                }
            }

            /** @var \Magento\SalesRule\Model\Rule $promoRule */
            $promoRule = $this->ruleFactory->create();
            $promoRule->setDiscountAmount($deducedAmount / $item->getQty());
            $promoRule->setDescription(implode(', ', $descriptions));
            $promoRule->setCouponType(Rule::COUPON_TYPE_NO_COUPON);
            $promoRule->setSimpleAction(Rule::BY_FIXED_ACTION);
            $promoRule->setStoreLabels(['Supplier Specials']);
            $address = $item->getAddress();
            $this->applyRule($item, $promoRule, $address, $couponCode);
        }

        return [$item, $rules, $skipValidation, $couponCode];
    }
}
