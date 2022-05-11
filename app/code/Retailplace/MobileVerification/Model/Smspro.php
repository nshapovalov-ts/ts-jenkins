<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Model;

use Retailplace\MobileVerification\Api\Data\SmsproInterface;

/**
 * Smspro Model
 */
class Smspro extends \Magecomp\Smspro\Model\Smspro implements SmsproInterface
{
    /**
     * Get Sms Verify Id
     *
     * @return int|null
     */
    public function getSmsVerifyId(): ?int
    {
        return (int) $this->getData(self::SMS_VERIFY_ID);
    }

    /**
     * Set Sms Verify Id
     *
     * @var int $smsVerifyId
     * @return $this
     */
    public function setSmsVerifyId(int $smsVerifyId): SmsproInterface
    {
        return $this->setData(self::SMS_VERIFY_ID, $smsVerifyId);
    }

    /**
     * Get Mobile Number
     *
     * @return string|null
     */
    public function getMobileNumber(): ?string
    {
        return $this->getData(self::MOBILE_NUMBER);
    }

    /**
     * Set Mobile Number
     *
     * @var string $mobileNumber
     * @return $this
     */
    public function setMobileNumber(string $mobileNumber): SmsproInterface
    {
        return $this->setData(self::MOBILE_NUMBER, $mobileNumber);
    }

    /**
     * Get Otp
     *
     * @return string|null
     */
    public function getOtp(): ?string
    {
        return $this->getData(self::OTP);
    }

    /**
     * Set Otp
     *
     * @var string $otp
     * @return $this
     */
    public function setOtp(string $otp): SmsproInterface
    {
        return $this->setData(self::OTP, $otp);
    }

    /**
     * Get Isverify
     *
     * @return bool|null
     */
    public function getIsverify(): ?bool
    {
        return (bool) $this->getData(self::ISVERIFY);
    }

    /**
     * Set Isverify
     *
     * @var bool $isverify
     * @return $this
     */
    public function setIsverify(bool $isverify): SmsproInterface
    {
        return $this->setData(self::ISVERIFY, (int) $isverify);
    }

    /**
     * Get Customer Id
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return (int) $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set Customer Id
     *
     * @var int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): SmsproInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get Date Created
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set Date Created
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): SmsproInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Date Updated
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set Date Updated
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): SmsproInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
