<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Controller\Ajax;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Retailplace\MobileVerification\Api\SmsproRepositoryInterface;

/**
 * Class ValidateOtp
 */
class ValidateOtp extends Action
{
    /** @var \Retailplace\MobileVerification\Api\SmsproRepositoryInterface */
    private $smsproRepository;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /** @var \Magento\Customer\Model\ResourceModel\Customer */
    private $customerResourceModel;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * ValidateOtp constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Retailplace\MobileVerification\Api\SmsproRepositoryInterface $smsproRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        SmsproRepositoryInterface $smsproRepository,
        Session $customerSession,
        DateTime $dateTime,
        CustomerResourceModel $customerResourceModel,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->smsproRepository = $smsproRepository;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->customerResourceModel = $customerResourceModel;
        $this->logger = $logger;
    }

    /**
     * Execute controller
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $result->setData([
                'is_success' => false,
                'response' => __('Customer should be logged in.')
            ]);
        } elseif (!$this->getRequest()->isAjax()) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->forward('noroute');
        } elseif (!$this->getRequest()->getParam('otp')) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $result->setData([
                'is_success' => false,
                'response' => __('OTP should be provided.')
            ]);
        } else {
            $result = $this->validateOtp();
        }

        return $result;
    }

    /**
     * Validate OTP
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function validateOtp(): ResultInterface
    {
        $otp = $this->getRequest()->getParam('otp');
        $customer = $this->customerSession->getCustomer();
        $smspro = $this->smsproRepository->getByCustomerId((int) $customer->getId());

        if ($smspro->getOtp() == $otp && !$smspro->getIsverify()) {
            try {
                $this->smsproRepository->delete($smspro);
                $customer->setData('date_phone_number_confirmed', $this->dateTime->gmtDate());
                $this->customerResourceModel->save($customer);
                $data = [
                    'is_success' => true,
                    'response' => __('OTP is valid.')
                ];
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $data = [
                    'is_success' => false,
                    'response' => __('Unable to update customer attributes.')
                ];
            }
        } else {
            $data = [
                'is_success' => false,
                'response' => __('The code is invalid.')
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}
