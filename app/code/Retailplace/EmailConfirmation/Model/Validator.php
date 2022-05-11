<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Model;

use Exception;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Validator
 */
class Validator
{
    /** @var string */
    public const XML_PATH_CUSTOMER_CREATE_ACCOUNT_VALIDATION_FAILURES_NUM = 'customer/create_account/email_validation_lockout_failures';
    public const XML_PATH_CUSTOMER_CREATE_ACCOUNT_VALIDATION_LOCKOUT_THRESHOLD = 'customer/create_account/email_validation_lockout_threshold';

    /** @var string */
    public const CUSTOMER_CONFIRMATION_ALT = 'confirmation_alt';
    public const EMAIL_VALIDATE_FAILURES_NUM = 'email_validate_failures_num';
    public const EMAIL_VALIDATE_LOCK_EXPIRES = 'email_validate_lock_expires';

    /** @var int */
    public const EMAIL_OTP_CODE_LENGTH = 5;

    /** @var \Magento\Customer\Model\ResourceModel\Customer */
    private $customerResourceModel;

    /** @var \Magento\Customer\Model\CustomerFactory */
    private $customerFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Magento\Customer\Model\Customer */
    private $customer;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $datetime;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        CustomerResourceModel $customerResourceModel,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        DateTime $datetime,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->customerResourceModel = $customerResourceModel;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->datetime = $datetime;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Validate Email OTP Code
     *
     * @param string $email
     * @param string $code
     * @return \Magento\Customer\Model\Customer|null
     */
    public function validateDigitalCode(string $email, string $code): ?Customer
    {
        $result = null;
        $customer = $this->getCustomer($email);
        if ($customer) {
            if ($customer->getData(self::CUSTOMER_CONFIRMATION_ALT) == $code) {
               $result = $customer;
            } else {
                $this->increaseFailureNum($customer);
            }
        }

        return $result;
    }

    /**
     * Email Validation Security Check
     *
     * @param string $email
     * @return int|null
     */
    public function checkSecuritySettings(string $email): ?int
    {
        $result = null;
        $customer = $this->getCustomer($email);
        if ($customer) {
            $lockExpires = $customer->getData(self::EMAIL_VALIDATE_LOCK_EXPIRES);
            if ($lockExpires && $lockExpires > $this->datetime->gmtDate()) {
                $result = $this->datetime->gmtTimestamp($lockExpires) - $this->datetime->gmtTimestamp();
            } elseif ($lockExpires && $lockExpires <= $this->datetime->gmtDate()) {
                $customer->setData(self::EMAIL_VALIDATE_LOCK_EXPIRES, null);
                $customer->setData(self::EMAIL_VALIDATE_FAILURES_NUM, 0);
                $this->saveCustomer($customer);
            }
        }

        return $result;
    }

    /**
     * Save Customer
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    private function saveCustomer(Customer $customer)
    {
        try {
            $this->customerResourceModel->save($customer);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Increase Validation Failures Num
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    private function increaseFailureNum(Customer $customer)
    {
        $failureNum = (int) $customer->getData(self::EMAIL_VALIDATE_FAILURES_NUM);
        $failureNum++;
        $maxFailureNum = $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_CREATE_ACCOUNT_VALIDATION_FAILURES_NUM);
        if ($maxFailureNum && $failureNum > $maxFailureNum) {
            $customer->setData(self::EMAIL_VALIDATE_FAILURES_NUM, 0);
            $this->lockValidation($customer);
        } else {
            $customer->setData(self::EMAIL_VALIDATE_FAILURES_NUM, $failureNum);
            $this->saveCustomer($customer);
        }
    }

    /**
     * Lockout Validation Attempts
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    private function lockValidation(Customer $customer)
    {
        $thresholdMin = $this->scopeConfig->getValue(self::XML_PATH_CUSTOMER_CREATE_ACCOUNT_VALIDATION_LOCKOUT_THRESHOLD);
        $gmtDateTimestamp = $this->datetime->gmtTimestamp() + ($thresholdMin * 60);
        $expiryDate = $this->datetime->gmtDate(
            \Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT,
            $gmtDateTimestamp
        );
        $customer->setData(
            self::EMAIL_VALIDATE_LOCK_EXPIRES,
            $expiryDate
        );
        $this->saveCustomer($customer);
    }

    /**
     * Load Customer
     *
     * @param string $email
     * @return \Magento\Customer\Model\Customer|null
     */
    private function getCustomer(string $email): ?Customer
    {
        if (!$this->customer) {
            try {
                $customer = $this->customerFactory->create();
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
                $customer->setWebsiteId($websiteId);
                $this->customerResourceModel->loadByEmail($customer, $email);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
                $customer = null;
            }

            $this->customer = $customer;
        }

        return $this->customer;
    }
}
