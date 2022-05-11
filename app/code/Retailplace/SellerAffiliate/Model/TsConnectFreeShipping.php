<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Combine;
use Magento\SalesRule\Model\RuleFactory;
use Psr\Log\LoggerInterface;

/**
 * Class TsConnectFreeShipping
 */
class TsConnectFreeShipping
{
    /** @var RuleFactory */
    private $ruleFactory;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var array */
    private $shippingRules;

    /** @var Json */
    private $serializer;

    /** @var LoggerInterface */
    private $logger;

    /**
     * TsConnectFreeShipping constructor
     *
     * @param RuleFactory $ruleFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param SellerAffiliateManagement $sellerAffiliateManagement
     * @param ConfigProvider $configProvider
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        RuleFactory $ruleFactory,
        CartRepositoryInterface $quoteRepository,
        SellerAffiliateManagement $sellerAffiliateManagement,
        ConfigProvider $configProvider,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->quoteRepository = $quoteRepository;
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param int $quoteId
     * @return Rule[]
     */
    public function getRulesData($quote): array
    {
        if ($this->shippingRules === null) {
            $this->shippingRules = [];
            try {
                //$quote = $this->quoteRepository->get($quoteId);
                $customerId = (int) $quote->getCustomer()->getId();
                $this->shippingRules = $this->prepareShippingRules($quote, $customerId);
            } catch (NoSuchEntityException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return $this->shippingRules;
    }

    /**
     * @return bool
     */
    public function isShippingRulesEnable(): bool
    {
        return $this->configProvider->isTsConnectShippingRuleEnable();
    }

    /**
     * Get shipping rules for the quote
     *
     * @param CartInterface $quote
     * @param int $customerId
     * @return Rule[]
     */
    private function prepareShippingRules(CartInterface $quote, int $customerId): array
    {
        $shippingRules = [];
        $quoteItems = [];
        $shippingRuleLabel = $this->configProvider->getShippingRuleLabel();
        $shopIds = $this->sellerAffiliateManagement->getAffiliateShopIdsByCustomer($customerId);
        foreach ($quote->getAllItems() as $item) {
            $shopId = $item->getData('mirakl_shop_id');
            if (in_array($shopId, $shopIds)) {
                $quoteItems[] = $item;
            }
        }

        $shippingAmount = 0;
        foreach ($quoteItems as $item) {
            $shippingAmount += $item->getMiraklBaseShippingFee();
        }

        if ($shippingAmount) {
            /** @var Rule $shippingRule */
            $shippingRule = $this->ruleFactory->create();
            $shippingRule->setSimpleAction(Rule::BY_FIXED_ACTION);
            $shippingRule->setDiscountAmount($shippingAmount);
            $shippingRule->setDescription($shippingRuleLabel);
            $shippingRule->setStoreLabels([$shippingRuleLabel]);
            $shippingRule->setApplyToShipping(1);
            $shippingRule->setActionsSerialized(
                $this->getActionData('NONE')
            );
            $shippingRule->setCouponType(Rule::COUPON_TYPE_NO_COUPON);
            $shippingRules[] = $shippingRule;
        }

        return $shippingRules;
    }

    /**
     * Get data for Rule Action
     *
     * @param string $sku
     * @return string
     */
    private function getActionData(string $sku): string
    {
        $result = '';
        try {
            $result = $this->serializer->serialize([
                'type'           => Combine::class,
                'attributeName'  => null,
                'operator'       => null,
                'value'          => '1',
                'aggregatorType' => 'all',
                'conditions'     => [
                    [
                        'type'               => Product::class,
                        'attributeName'      => 'sku',
                        'operator'           => '==',
                        'value'              => $sku,
                        'aggregatorType'     => '',
                        'is_value_processed' => false,
                        'attribute_scope'    => ''
                    ]
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->logger->critical($e->getMessage());
        }

        return $result;
    }
}
