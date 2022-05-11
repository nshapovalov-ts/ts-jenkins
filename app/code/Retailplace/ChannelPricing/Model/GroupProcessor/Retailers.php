<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model\GroupProcessor;

use Magento\Customer\Api\Data\CustomerInterface;
use Retailplace\ChannelPricing\Api\GroupProcessorInterface;
use Retailplace\CustomerAccount\Block\Widget\BusinessType;

/**
 * Class Retailers
 */
class Retailers extends AbstractProcessor implements GroupProcessorInterface
{
    /** @var string */
    public const BUSINESS_TYPE_RETAIL_LABEL = 'Retail';
    public const GROUP_CODE = 'Retailers';

    /**
     * Check Processor condition to apply Customer Group
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return bool
     */
    public function checkCondition(CustomerInterface $customer): bool
    {
        $result = false;
        $businessType = $customer->getCustomAttribute(BusinessType::ATTRIBUTE_CODE);
        if ($businessType) {
            $businessTypeValues = explode(',', $businessType->getValue());
            $businessTypeTrigger = $this->getAttributeValueIdByLabel(
                BusinessType::ATTRIBUTE_CODE,
                self::BUSINESS_TYPE_RETAIL_LABEL
            );

            if (in_array($businessTypeTrigger, $businessTypeValues)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Get Customers List with Current Processor criteria
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface[]|null
     */
    public function getCustomersList(): ?array
    {
        $customersList = null;

        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(
                BusinessType::ATTRIBUTE_CODE,
                $this->getAttributeValueIdByLabel(
                    BusinessType::ATTRIBUTE_CODE,
                    self::BUSINESS_TYPE_RETAIL_LABEL
                ),
                'finset'
            )
            ->addFilter(CustomerInterface::GROUP_ID, $this->getGroupId(), 'neq')
            ->create();

        try {
            $customersList = $this->customerRepository->getList($searchCriteria);
            $customersList = $customersList->getItems();
        } catch (\Exception $e) {
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
}
