<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model;

use Exception;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CartRulesUpdater
 */
class CartRulesUpdater
{
    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var \Magento\SalesRule\Api\RuleRepositoryInterface */
    private $ruleRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * CartRulesUpdater constructor.
     *
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        GroupRepositoryInterface $customerGroupRepository,
        RuleRepositoryInterface $ruleRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->customerGroupRepository = $customerGroupRepository;
        $this->ruleRepository = $ruleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Update all existing Cart Price Rules with the passed Groups list
     *
     * @param string[]|null $groups
     * @return int
     */
    public function updateCartRulesWithGroup(?array $groups = null): int
    {
        $newGroupsId = $this->getGroupsId($groups);
        $counter = 0;

        if (count($newGroupsId)) {
            $ruleSearchCriteria = $this->searchCriteriaBuilder->create();

            try {
                $rules = $this->ruleRepository->getList($ruleSearchCriteria);
                foreach ($rules->getItems() as $rule) {
                    $groupIds = $rule->getCustomerGroupIds();
                    $groupIds = array_unique(array_merge($groupIds, $newGroupsId));
                    $rule->setCustomerGroupIds($groupIds);
                    $this->ruleRepository->save($rule);
                    $counter++;
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $counter;
    }

    /**
     * Collect Customer Groups Id
     *
     * @param string[]|null $groups
     * @return array
     */
    private function getGroupsId(?array $groups = null): array
    {
        if ($groups) {
            $groupSearchCriteria = $this->searchCriteriaBuilder
                ->addFilter(GroupInterface::CODE, $groups, 'in')
                ->create();
        } else {
            $groupSearchCriteria = $this->searchCriteriaBuilder
                ->create();
        }

        $groupIds = [];
        try {
            $groupsList = $this->customerGroupRepository->getList($groupSearchCriteria);
            foreach ($groupsList->getItems() as $group) {
                $groupIds[] = $group->getId();
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $groupIds;
    }
}
