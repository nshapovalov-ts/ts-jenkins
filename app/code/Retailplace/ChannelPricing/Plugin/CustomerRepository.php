<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Retailplace\ChannelPricing\Model\CustomerGroupMapper;
use Magento\Customer\Model\ResourceModel\CustomerRepository as MagentoCustomerRepository;

/**
 * Class CustomerRepository
 */
class CustomerRepository
{
    /** @var \Retailplace\ChannelPricing\Model\CustomerGroupMapper */
    private $customerGroupMapper;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /**
     * CustomerRepository constructor.
     *
     * @param \Retailplace\ChannelPricing\Model\CustomerGroupMapper $customerGroupMapper
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        CustomerGroupMapper $customerGroupMapper,
        Session $customerSession
    ) {
        $this->customerGroupMapper = $customerGroupMapper;
        $this->customerSession = $customerSession;
    }

    /**
     * Update Customer Group depends on Attributes before save and add new Group Id to the Session
     *
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string|null $passwordHash
     * @return array
     */
    public function beforeSave(MagentoCustomerRepository $subject, CustomerInterface $customer, $passwordHash = null)
    {
        $groupId = $this->customerGroupMapper->getGroupIdByCustomer($customer);
        if ($groupId) {
            $customer->setGroupId($groupId);
            if ($this->customerSession->getCustomer()->getId() == $customer->getId()) {
                $this->customerSession->setCustomerGroupId($groupId);
            }
        }

        return [$customer, $passwordHash];
    }
}
