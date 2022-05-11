<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Controller\Ajax;

use Exception;
use Magecomp\Smspro\Helper\Apicall as ApiHelper;
use Magecomp\Smspro\Helper\Customer as CustomerHelper;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Customer\Model\Session;
use Magento\Email\Model\Template\Filter;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Retailplace\CustomerAccount\Block\Widget\PhoneNumber;
use Retailplace\MobileVerification\Api\SmsproRepositoryInterface;

/**
 * Class UpdateCustomer
 */
class SendOtp extends Action
{
    /** @var int */
    public const TIME_BETWEEN_SMS_SEC = 60;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magecomp\Smspro\Helper\Customer */
    private $customerHelper;

    /** @var \Magento\Email\Model\Template\Filter */
    private $emailFilter;

    /** @var \Retailplace\MobileVerification\Api\SmsproRepositoryInterface */
    private $smsproRepository;

    /** @var \Magecomp\Smspro\Helper\Apicall */
    private $apiHelper;

    /** @var \Magento\Customer\Model\ResourceModel\Customer */
    private $customerResourceModel;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * UpdateCustomer constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magecomp\Smspro\Helper\Customer $customerHelper
     * @param \Magento\Email\Model\Template\Filter $emailFilter
     * @param \Retailplace\MobileVerification\Api\SmsproRepositoryInterface $smsproRepository
     * @param \Magecomp\Smspro\Helper\Apicall $apiHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerHelper $customerHelper,
        Filter $emailFilter,
        SmsproRepositoryInterface $smsproRepository,
        ApiHelper $apiHelper,
        CustomerResourceModel $customerResourceModel,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->customerSession = $customerSession;
        $this->customerHelper = $customerHelper;
        $this->emailFilter = $emailFilter;
        $this->smsproRepository = $smsproRepository;
        $this->apiHelper = $apiHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Execute controller
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->forward('noroute');
        } else {
            $this->updatePhone();
            $result = $this->sendResponse($this->sendOtp());
        }

        return $result;
    }

    /**
     * Update Customer Phone number
     */
    private function updatePhone()
    {
        $customer = $this->customerSession->getCustomer();
        $customer->setData(PhoneNumber::ATTRIBUTE_CODE, $this->getPhone());
        try {
            $this->customerResourceModel->save($customer);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Generate and save OTP
     *
     * @return string
     */
    private function getOtp(): string
    {
        $otp = $this->customerHelper->getOtp();

        $smspro = $this->smsproRepository->getByCustomerId((int) $this->customerSession->getCustomerId());

        $smspro->setCustomerId((int) $this->customerSession->getCustomerId());
        $smspro->setMobileNumber($this->getPhone());
        $smspro->setOtp($otp);

        try {
            $this->smsproRepository->save($smspro);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $otp;
    }

    /**
     * Get Phone from Request parameter
     *
     * @return string
     */
    private function getPhone(): string
    {
        $phone = $this->getRequest()->getParam('phone') ?: '';
        if (!$phone) {
            return '';
        }

        return '+61' . ltrim($phone, "0");
    }

    /**
     * Send OTP code
     *
     * @return string|bool
     */
    private function sendOtp()
    {
        if ($this->checkLastOtpTime()) {
            $otp = $this->getOtp();
            $this->emailFilter->setVariables([
                'otp' => $otp
            ]);
            $message = $this->customerHelper->getMobileConfirmationUserTemplate();
            $finalMessage = $this->emailFilter->filter($message);

            $result = $this->apiHelper->callApiUrl($this->getPhone(), $finalMessage);
        } else {
            $result = __('Please wait for 1 minute before requesting a new code.');
        }

        return $result;
    }

    /**
     * Check last SMS time
     *
     * @return bool
     */
    private function checkLastOtpTime(): bool
    {
        $smspro = $this->smsproRepository->getByCustomerId((int) $this->customerSession->getCustomerId());
        $diffSeconds = $this->dateTime->gmtDate('U') - $this->dateTime->gmtDate('U', $smspro->getUpdatedAt());

        return !$smspro->getSmsVerifyId() || $diffSeconds > self::TIME_BETWEEN_SMS_SEC;
    }

    /**
     * Send Ajax Response
     *
     * @param string|bool $otpResult
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function sendResponse($otpResult): ResultInterface
    {
        if (is_bool($otpResult) && !$otpResult) {
            $data = [
                'is_success' => false,
                'response'   => __('Unable to send OTP code.')
            ];
        } elseif (is_bool($otpResult) && $otpResult) {
            $data = [
                'is_success' => true,
                'response'   => __('OTP code was sent to %1', $this->getPhone())
            ];
        } else {
            $data = [
                'is_success' => false,
                'response'   => $otpResult
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
