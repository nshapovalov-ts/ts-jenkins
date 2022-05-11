<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Plugin;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\Account\Confirm;
use Psr\Log\LoggerInterface;

/**
 * Class CustomerConfirm
 */
class CustomerConfirm
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
     * Redirect after Email Validation
     *
     * @param \Magento\Customer\Controller\Account\Confirm $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function afterExecute(Confirm $subject, \Magento\Framework\Controller\Result\Redirect $result)
    {
        $customerId = $subject->getRequest()->getParam('id');
        $customer = null;
        if ($customerId) {
            try {
                $customer = $this->customerRepository->getById($customerId);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        if ($customer) {
            if (!$customer->getConfirmation()) {
                $result->setPath('customer/account/edit');
            }
        }

        return $result;
    }
}
