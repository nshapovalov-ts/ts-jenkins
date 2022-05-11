<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model\GroupProcessor;

use Exception;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Config as EavConfig;
use Retailplace\ChannelPricing\Api\GroupProcessorInterface;

/**
 * Class AbstractProcessor
 */
abstract class AbstractProcessor implements GroupProcessorInterface
{
    /** @var \Magento\Eav\Model\Config */
    protected $eavConfig;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    protected $searchCriteriaBuilderFactory;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var \Magento\Customer\Api\Data\GroupInterface[] */
    protected $groupsList;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    protected $customerGroupRepository;

    /** @var \Magento\Framework\Api\FilterBuilder */
    protected $filterBuilder;

    /** @var \Magento\Framework\Api\Search\FilterGroupBuilder */
    protected $filterGroupBuilder;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var array */
    private $attributeValueIds;

    /**
     * AbstractProcessor constructor.
     *
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        EavConfig $eavConfig,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CustomerRepositoryInterface $customerRepository,
        GroupRepositoryInterface $customerGroupRepository,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        LoggerInterface $logger
    ) {
        $this->eavConfig = $eavConfig;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->logger = $logger;
    }

    /**
     * Get Customer Mapped Attribute Value Id by it's Label.
     *
     * @param string $attributeCode
     * @param string $valueLabel
     * @return string|int|null
     */
    protected function getAttributeValueIdByLabel(string $attributeCode, string $valueLabel)
    {
        if (isset($this->attributeValueIds[$attributeCode][$valueLabel])) {
            return $this->attributeValueIds[$attributeCode][$valueLabel];
        }

        $attributeValueId = null;
        try {
            $attribute = $this->eavConfig
                ->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $attributeCode);

            if ($attribute) {
                foreach ($attribute->getOptions() as $option) {
                    if ($option->getLabel() == $valueLabel) {
                        $attributeValueId = $option->getValue();
                    }
                    $this->attributeValueIds[$attributeCode][$option->getLabel()] = $option->getValue();
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $attributeValueId;
    }

    /**
     * Get Group Id for Customer depends on Mapped Customer Attribute.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return int|null
     */
    public function getGroupIdByCustomer(CustomerInterface $customer): ?int
    {
        $groupId = null;

        if ($this->checkCondition($customer)) {
            $groupId = $this->getGroupId();
        }

        return $groupId;
    }

    /**
     * Get Customers List with Current Processor criteria
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface[]|null
     */
    public function getCustomersList(): ?array
    {
        return [];
    }

    /**
     * Get Customer Group Id by Code.
     *
     * @return int|null
     */
    public function getGroupId(): ?int
    {
        $groupId = null;

        $groups = $this->getGroupsList();
        if ($groups) {
            foreach ($groups as $group) {
                if ($group->getCode() == $this->getGroupCode()) {
                    $groupId = (int) $group->getId();
                }
            }
        }

        return $groupId;
    }

    /**
     * Load all Customer Groups
     *
     * @return \Magento\Customer\Api\Data\GroupInterface[]|null
     */
    private function getGroupsList(): ?array
    {
        if (!$this->groupsList) {
            /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder->create();

            try {
                $groups = $this->customerGroupRepository->getList($searchCriteria);
                $this->groupsList = $groups->getItems();
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return $this->groupsList;
    }

    /**
     * Check Processor condition to apply Customer Group
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return bool
     */
    abstract public function checkCondition(CustomerInterface $customer): bool;

    /**
     * Get Current Processor Group Code
     *
     * @return string
     */
    abstract public function getGroupCode(): string;
}
