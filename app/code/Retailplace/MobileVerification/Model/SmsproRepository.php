<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Model;

use Magecomp\Smspro\Model\Smspro;
use Retailplace\MobileVerification\Api\Data\SmsproInterface;
use Retailplace\MobileVerification\Api\SmsproRepositoryInterface;
use Magecomp\Smspro\Model\ResourceModel\Smspro as SmsproResourceModel;
use Retailplace\MobileVerification\Api\Data\SmsproInterfaceFactory as SmsproFactory;

/**
 * Class SmsproRepository
 */
class SmsproRepository implements SmsproRepositoryInterface
{
    /** @var \Magecomp\Smspro\Model\ResourceModel\Smspro */
    private $smsproResourceModel;

    /** @var \Retailplace\MobileVerification\Api\Data\SmsproInterfaceFactory */
    private $smsproFactory;

    /**
     * SmsproRepository constructor
     *
     * @param \Magecomp\Smspro\Model\ResourceModel\Smspro $smsproResourceModel
     * @param \Retailplace\MobileVerification\Api\Data\SmsproInterfaceFactory $smsproFactory
     */
    public function __construct(
        SmsproResourceModel $smsproResourceModel,
        SmsproFactory $smsproFactory
    ) {
        $this->smsproResourceModel = $smsproResourceModel;
        $this->smsproFactory = $smsproFactory;
    }

    /**
     * Save Smspro
     *
     * @param \Retailplace\MobileVerification\Api\Data\SmsproInterface|\Magecomp\Smspro\Model\Smspro $smspro
     * @return \Retailplace\MobileVerification\Api\Data\SmsproInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(SmsproInterface $smspro): SmsproInterface
    {
        $this->smsproResourceModel->save($smspro);

        return $smspro;
    }

    /**
     * Delete Smspro
     *
     * @param \Retailplace\MobileVerification\Api\Data\SmsproInterface $smspro
     * @return bool
     * @throws \Exception
     */
    public function delete(SmsproInterface $smspro): bool
    {
        $this->smsproResourceModel->delete($smspro);

        return true;
    }

    /**
     * Get object
     *
     * @param string $otp
     * @return \Retailplace\MobileVerification\Api\Data\SmsproInterface
     */
    public function getByOtp(string $otp): SmsproInterface
    {
        $smspro = $this->smsproFactory->create();
        $this->smsproResourceModel->load($smspro, $otp, SmsproInterface::OTP);

        return $smspro;
    }

    /**
     * Get object
     *
     * @param int $customerId
     * @return \Retailplace\MobileVerification\Api\Data\SmsproInterface
     */
    public function getByCustomerId(int $customerId): SmsproInterface
    {
        $smspro = $this->smsproFactory->create();
        $this->smsproResourceModel->load($smspro, $customerId, SmsproInterface::CUSTOMER_ID);

        return $smspro;
    }
}
