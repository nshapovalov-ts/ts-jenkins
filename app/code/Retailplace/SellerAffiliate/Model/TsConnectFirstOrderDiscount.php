<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model;

use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\SalesRule\Model\Rule\Condition\Product;
use Magento\SalesRule\Model\Rule\Condition\Product\Combine;
use Mirakl\Core\Model\ShopFactory as ShopFactory;
use Mirakl\Core\Model\ResourceModel\Shop as ShopResourceModel;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TsConnectFirstOrderDiscount implements discount for TS connect first order
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class TsConnectFirstOrderDiscount
{
    /** @var RuleFactory */
    private $ruleFactory;

    /** @var CartRepositoryInterface */
    private $quoteRepository;

    /** @var SellerAffiliateManagement */
    private $sellerAffiliateManagement;

    /** @var ShopFactory */
    private $shopFactory;

    /** @var ShopResourceModel */
    private $shopResourceModel;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var TimezoneInterface */
    private $date;

    /** @var Json */
    private $serializer;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $promoRules;

    /**
     * @param RuleFactory $ruleFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param ShopResourceModel $shopResourceModel
     * @param ShopFactory $shopFactory
     * @param SellerAffiliateManagement $sellerAffiliateManagement
     * @param ConfigProvider $configProvider
     * @param TimezoneInterface $date
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        RuleFactory $ruleFactory,
        CartRepositoryInterface $quoteRepository,
        ShopResourceModel $shopResourceModel,
        ShopFactory $shopFactory,
        SellerAffiliateManagement $sellerAffiliateManagement,
        ConfigProvider $configProvider,
        TimezoneInterface $date,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->quoteRepository = $quoteRepository;
        $this->shopResourceModel = $shopResourceModel;
        $this->shopFactory = $shopFactory;
        $this->sellerAffiliateManagement = $sellerAffiliateManagement;
        $this->configProvider = $configProvider;
        $this->date = $date;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param int $quoteId
     * @return Rule[]
     */
    public function getRulesData($quote): array
    {
        if ($this->promoRules === null) {
            $this->promoRules = [];
            try {
                //$quote = $this->quoteRepository->get($quoteId);
                $customerId = (int) $quote->getCustomer()->getId();
                $this->promoRules = $this->preparePromoRules($quote, $customerId);
            } catch (NoSuchEntityException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return $this->promoRules;
    }

    /**
     * @return bool
     */
    public function isPromoRulesEnable(): bool
    {
        return $this->configProvider->isTsConnectPromoRuleEnable();
    }

    /**
     * @param Quote $quote
     * @param int $customerId
     * @return Rule[]
     */
    private function preparePromoRules(Quote $quote, int $customerId): array
    {
        $quoteItems = [];
        $promoRules = [];
        $promoRuleLabel = $this->configProvider->getPromoRuleLabel();
        $availableShopIds = $this->getShopIds($customerId);
        foreach ($quote->getAllItems() as $item) {
            $shopId = $item->getData('mirakl_shop_id');
            if (in_array($shopId, $availableShopIds)) {
                $quoteItems[$shopId][] = $item;
            }
        }
        foreach ($quoteItems as $shopId => $shopItem) {
            $discountAmount = $this->getDiscountAmountByShop($shopId);
            /** @var Rule $promoRule */
            $promoRule = $this->ruleFactory->create();
            $promoRule->setDiscountAmount($discountAmount);
            $promoRule->setDescription($promoRuleLabel);
            $promoRule->setCouponType(Rule::COUPON_TYPE_NO_COUPON);
            $promoRule->setSimpleAction(Rule::CART_FIXED_ACTION);
            $promoRule->setStoreLabels([$promoRuleLabel]);
            $promoRule->setActionsSerialized(
                $this->getActionData($shopId)
            );
            $promoRules[$shopId] = $promoRule;
        }

        return $promoRules;
    }

    /**
     * @param int $customerId
     * @return array
     */
    private function getShopIds(int $customerId): array
    {
        $availableShopIds = [];
        $currentServerDateTime = $this->date->date()->format('Y-m-d H:i:s');
        $shopIds = $this->sellerAffiliateManagement->getAffiliateShopIdsByCustomer($customerId);
        foreach ($shopIds as $shopId) {
            $isFirstOrder = $this->sellerAffiliateManagement->isFirstOrderForAffiliatedCustomer(
                $customerId,
                (int) $shopId,
                $currentServerDateTime
            );
            if ($isFirstOrder) {
                $availableShopIds[] = $shopId;
            }
        }

        return $availableShopIds;
    }

    /**
     * @param int $shopId
     * @return float
     */
    private function getDiscountAmountByShop(int $shopId): float
    {
        $shop = $this->shopFactory->create();
        $this->shopResourceModel->load($shop, $shopId);
        $discountAmount = $shop->getData(SellerTagsAttributes::TS_FIRST_ORDER_DISCOUNT_AMOUNT);
        if (!$discountAmount) {
            $discountAmount = $this->configProvider->getDefaultAmountFirstOrder();
        }

        return (float) $discountAmount;
    }

    /**
     * Get data for Rule Action
     *
     * @param int $shopId
     * @return string
     */
    private function getActionData(int $shopId): string
    {
        $result = '';
        try {
            $result = $this->serializer->serialize([
                'type'               => Combine::class,
                'attribute'          => null,
                'operator'           => null,
                'value'              => '1',
                'is_value_processed' => null,
                'aggregator'         => 'all',
                'conditions'         => [
                    [
                        'type'               => Product::class,
                        'attribute'          => 'quote_item_mirakl_shop_id',
                        'operator'           => '==',
                        'value'              => (string) $shopId,
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
