<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Plugin;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResourceModel;
use Psr\Log\LoggerInterface;
use Retailplace\CustomerAccount\Block\Widget\BusinessName;

/**
 * Class UpdateCompanyAttribute
 */
class UpdateCompanyAttribute
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
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
     * Update Address Company
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address $subject
     * @param \Magento\Quote\Model\Quote\Address $object
     * @return \Magento\Quote\Model\Quote\Address[]
     */
    public function beforeSave(QuoteAddressResourceModel $subject, Address $object): array
    {
        if (!$object->getCompany() && $object->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($object->getCustomerId());
                $attribute = $customer->getCustomAttribute(BusinessName::ATTRIBUTE_CODE);
                if ($attribute) {
                    $object->setCompany($attribute->getValue());
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        return [$object];
    }
}
