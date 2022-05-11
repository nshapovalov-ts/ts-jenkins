<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Api;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Interface GroupProcessorInterface
 */
interface GroupProcessorInterface
{
    /**
     * Check Processor condition to apply Customer Group
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return bool
     */
    public function checkCondition(CustomerInterface $customer): bool;

    /**
     * Get Group Id by Customer depends on Condition
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return int|null
     */
    public function getGroupIdByCustomer(CustomerInterface $customer): ?int;

    /**
     * Get Current Processor Group Code
     *
     * @return string
     */
    public function getGroupCode(): string;

    /**
     * Get Current Processor Group Id
     *
     * @return int|null
     */
    public function getGroupId(): ?int;

    /**
     * Get Customers List with Current Processor criteria
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface[]|null
     */
    public function getCustomersList(): ?array;
}
