<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Model;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry as MagentoCustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Customer\Model\Data\CustomerSecureFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CustomerRegistry
 */
class CustomerRegistry extends MagentoCustomerRegistry
{
    /** @var array */
    private $customerSecureRegistryById = [];

    /** @var \Magento\Customer\Model\Data\CustomerSecureFactory */
    private $customerSecureFactory;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Data\CustomerSecureFactory $customerSecureFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerSecureFactory $customerSecureFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($customerFactory, $customerSecureFactory, $storeManager);

        $this->customerSecureFactory = $customerSecureFactory;
    }

    /**
     * Add confirmation_alt field to Secure Data
     *
     * @param int $customerId
     * @return \Magento\Customer\Model\Data\CustomerSecure
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @see \Magento\Customer\Model\CustomerRegistry::retrieveSecureData()
     */
    public function retrieveSecureData($customerId)
    {
        if (isset($this->customerSecureRegistryById[$customerId])) {
            return $this->customerSecureRegistryById[$customerId];
        }
        /** @var Customer $customer */
        $customer = $this->retrieve($customerId);
        /** @var $customerSecure CustomerSecure*/
        $customerSecure = $this->customerSecureFactory->create();
        $customerSecure->setPasswordHash($customer->getPasswordHash());
        $customerSecure->setRpToken($customer->getRpToken());
        $customerSecure->setRpTokenCreatedAt($customer->getRpTokenCreatedAt());
        $customerSecure->setDeleteable($customer->isDeleteable());
        $customerSecure->setFailuresNum($customer->getFailuresNum());
        $customerSecure->setFirstFailure($customer->getFirstFailure());
        $customerSecure->setLockExpires($customer->getLockExpires());
        $customerSecure->setData(
            Validator::CUSTOMER_CONFIRMATION_ALT,
            $customer->getData(Validator::CUSTOMER_CONFIRMATION_ALT)
        );
        $this->customerSecureRegistryById[$customer->getId()] = $customerSecure;

        return $customerSecure;
    }
}
