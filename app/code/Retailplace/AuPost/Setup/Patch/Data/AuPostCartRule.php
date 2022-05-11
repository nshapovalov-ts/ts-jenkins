<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Setup\Patch\Data;

use Exception;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\SalesRule\Api\Data\ConditionInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\Data\Condition;
use Magento\SalesRule\Model\Data\ConditionFactory;
use Magento\SalesRule\Model\Data\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Retailplace\ChannelPricing\Setup\Patch\Data\AddAuPostData;
use Retailplace\AuPost\Model\Rule\Condition\AuPost as AuPostCondition;
use Magento\SalesRule\Model\Rule\Condition\Product\Combine as RuleProductCondition;
use Magento\SalesRule\Model\Rule\Condition\Combine as RuleCondition;

/**
 * Class AuPostCartRule
 */
class AuPostCartRule implements DataPatchInterface
{
    /** @var string */
    public const RULE_NAME = 'AU Post Full Discount';
    public const RULE_DESCRIPTION = 'Full Discount for AU Post Customers to buy Products from AU Post Sellers';

    /** @var int */
    public const SORT_ORDER = 0;

    /** @var float */
    public const DISCOUNT_AMOUNT = 100.0000;

    /** @var \Magento\SalesRule\Model\Data\ConditionFactory */
    private $conditionFactory;

    /** @var \Magento\SalesRule\Model\Data\RuleFactory */
    private $ruleFactory;

    /** @var \Magento\SalesRule\Api\RuleRepositoryInterface */
    private $ruleRepository;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Magento\Customer\Model\ResourceModel\GroupRepository */
    private $groupRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AuPostCartRule constructor.
     *
     * @param \Magento\SalesRule\Model\Data\ConditionFactory $conditionFactory
     * @param \Magento\SalesRule\Model\Data\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\ResourceModel\GroupRepository $groupRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ConditionFactory $conditionFactory,
        RuleFactory $ruleFactory,
        RuleRepositoryInterface $ruleRepository,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        GroupRepository $groupRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->conditionFactory = $conditionFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleRepository = $ruleRepository;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->createCartPriceRule();
    }

    /**
     * Add new Cart Price Rule to get 100% discount for AU Post Customers to buy from AU Post Sellers
     */
    private function createCartPriceRule()
    {
        /** @var \Magento\SalesRule\Api\Data\RuleInterface $rule */
        $rule = $this->ruleFactory->create();

        $rule->setName(self::RULE_NAME);
        $rule->setDescription(self::RULE_DESCRIPTION);
        $rule->setFromDate($this->dateTime->gmtDate());
        $rule->setToDate(null);
        $rule->setUsesPerCustomer(0);
        $rule->setIsActive(1);
        $rule->setWebsiteIds($this->getStoreIds());
        $rule->setStopRulesProcessing(0);
        $rule->setIsAdvanced(1);
        $rule->setProductIds(null);
        $rule->setSortOrder(self::SORT_ORDER);
        $rule->setSimpleAction(RuleInterface::DISCOUNT_ACTION_BY_PERCENT);
        $rule->setDiscountAmount(self::DISCOUNT_AMOUNT);
        $rule->setDiscountQty(null);
        $rule->setDiscountStep(0);
        $rule->setApplyToShipping(0);
        $rule->setIsRss(1);
        $rule->setCouponType(RuleInterface::COUPON_TYPE_NO_COUPON);
        $rule->setUseAutoGeneration(0);
        $rule->setCondition($this->generateCondition());
        $rule->setActionCondition($this->generateAction());
        $rule->setCustomerGroupIds($this->getGroupIds());

        try {
            $this->ruleRepository->save($rule);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * Get Group ID by Code
     *
     * @return int[]
     */
    private function getGroupIds(): array
    {
        $groupIds = [];
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(GroupInterface::CODE, AuPost::GROUP_CODE)
            ->create();

        try {
            $groups = $this->groupRepository->getList($searchCriteria)->getItems();
            foreach ($groups as $group) {
                $groupIds[] = (int) $group->getId();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $groupIds;
    }

    /**
     * Get all stores IDs
     *
     * @return int[]
     */
    private function getStoreIds(): array
    {
        $storesIds = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $storesIds[] = (int) $store->getId();
        }

        return $storesIds;
    }

    /**
     * Add Action to Product Applying Criteria
     *
     * @return \Magento\SalesRule\Api\Data\ConditionInterface
     */
    private function generateAction(): ConditionInterface
    {
        $action = [
            'type' => RuleProductCondition::class,
            'attributeName' => null,
            'operator' => null,
            'value' => 1,
            'aggregatorType' => 'all',
        ];

        $subAction = [
            'type' => AuPostCondition::class,
            'attributeName' => AuPostCondition::IS_AU_POST_PRODUCT,
            'operator' => '==',
            'value' => 1,
            'aggregatorType' => null,
        ];

        return $this->createConditions($action, $this->createConditions($subAction));
    }

    /**
     * Trigger Rule for all Quotes
     *
     * @return \Magento\SalesRule\Api\Data\ConditionInterface
     */
    private function generateCondition(): ConditionInterface
    {
        $condition = [
            'type' => RuleCondition::class,
            'attributeName' => null,
            'operator' => null,
            'value' => 1,
            'aggregatorType' => 'all',
        ];

        return $this->createConditions($condition);
    }

    /**
     * Create Condition from Array
     *
     * @param array $conditionData
     * @param \Magento\SalesRule\Api\Data\ConditionInterface|null $subCondition
     * @return \Magento\SalesRule\Model\Data\Condition
     */
    private function createConditions(array $conditionData, ?ConditionInterface $subCondition = null): ConditionInterface
    {
        /**  @var $condition Condition */
        $condition = $this->conditionFactory->create();
        $condition->setConditionType($conditionData['type'])
            ->setAttributeName($conditionData['attributeName'])
            ->setOperator($conditionData['operator'])
            ->setValue($conditionData['value'])
            ->setAggregatorType($conditionData['aggregatorType']);
        if ($subCondition) {
            $condition->setConditions([$subCondition]);
        }

        return $condition;
    }

    /**
     * Get array of patches that have to be executed prior to this
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [
            AddAuPostData::class
        ];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
