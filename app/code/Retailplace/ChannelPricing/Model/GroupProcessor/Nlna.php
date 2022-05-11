<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model\GroupProcessor;

use Magento\Customer\Api\Data\CustomerInterface;
use Retailplace\ChannelPricing\Api\GroupProcessorInterface;
use Retailplace\CustomerAccount\Block\Widget\Industry;
use Retailplace\CustomerAccount\Block\Widget\MyNetwork;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Api\Search\FilterGroup;

/**
 * Class Nlna
 */
class Nlna extends AbstractProcessor implements GroupProcessorInterface
{
    /** @var string */
    public const NETWORK_TYPE_NLNA_CODE = 'nlna';
    public const NETWORK_TYPE_NLNA_LABEL = 'National Lotteries and Newsagents Association';
    public const NETWORK_TYPE_VAN_CODE = 'van';
    public const NETWORK_TYPE_VAN_LABEL = 'Victorian Association for Newsagents';
    public const INDUSTRY_TYPE_NEWSAGENCY = 'newsagency';
    public const GROUP_CODE = 'NLNA';

    /**
     * Check Processor condition to apply Customer Group
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    public function checkCondition(CustomerInterface $customer): bool
    {
        $result = false;
        $myNetwork = $customer->getCustomAttribute(MyNetwork::ATTRIBUTE_CODE);
        if ($myNetwork) {
            $myNetworkValues = explode(',', $myNetwork->getValue());
            if (in_array(self::NETWORK_TYPE_NLNA_CODE, $myNetworkValues) || in_array(self::NETWORK_TYPE_VAN_CODE, $myNetworkValues)) {
                $result = true;
            }
        }

        $industry = $customer->getCustomAttribute(Industry::ATTRIBUTE_CODE);
        if ($industry) {
            $industryValues = explode(',', $industry->getValue());
            if (in_array(self::INDUSTRY_TYPE_NEWSAGENCY, $industryValues)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Get Customers List with Current Processor criteria
     *
     * @return CustomerInterface[]|null
     */
    public function getCustomersList(): ?array
    {
        $customersList = null;

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->setFilterGroups([$this->getMyNetworkFilterGroup()])
            ->addFilter(CustomerInterface::GROUP_ID, $this->getGroupId(), 'neq')
            ->create();

        try {
            $customersList = $this->customerRepository->getList($searchCriteria);
            $customersList = $customersList->getItems();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $customersList;
    }

    /**
     * Get Current Processor Group Code
     *
     * @return string
     */
    public function getGroupCode(): string
    {
        return self::GROUP_CODE;
    }

    /**
     * Get Filter Group for OR Condition
     *
     * @return AbstractSimpleObject|FilterGroup
     */
    private function getMyNetworkFilterGroup(): AbstractSimpleObject
    {
        $nlnaFilter = $this->filterBuilder
            ->setField(MyNetwork::ATTRIBUTE_CODE)
            ->setValue(self::NETWORK_TYPE_NLNA_CODE)
            ->setConditionType('finset')
            ->create();

        $vanaFilter = $this->filterBuilder
            ->setField(MyNetwork::ATTRIBUTE_CODE)
            ->setValue(self::NETWORK_TYPE_VAN_CODE)
            ->setConditionType('finset')
            ->create();

        $filtersGroup = $this->filterGroupBuilder
            ->setFilters([$nlnaFilter, $vanaFilter])
            ->create();

        return $filtersGroup;
    }
}
