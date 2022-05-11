<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Plugin;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Psr\Log\LoggerInterface;

/**
 * PhoneChange
 */
class PhoneChange
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * PhoneChange constructor
     *
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return \Magento\Customer\Model\Customer
     */
    public function beforeSave(Customer $customer): Customer
    {
        try {
            $customerCurrent = $this->customerRepository->getById($customer->getId());
            $phoneAttribute = $customerCurrent->getCustomAttribute('phone_number');
            if ($phoneAttribute && $phoneAttribute->getValue() != $customer->getPhoneNumber()) {
                $customer->setData('date_phone_number_confirmed', null);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $customer;
    }
}
