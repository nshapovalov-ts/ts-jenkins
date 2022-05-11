<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Api\Data;

interface SmsproInterface
{
    /** @var string */
    const SMS_VERIFY_ID = 'sms_verify_id';
    const MOBILE_NUMBER = 'mobile_number';
    const OTP = 'otp';
    const ISVERIFY = 'isverify';
    const CUSTOMER_ID = 'customer_id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get Sms Verify Id
     *
     * @return int|null
     */
    public function getSmsVerifyId(): ?int;

    /**
     * Set Sms Verify Id
     *
     * @var int $smsVerifyId
     * @return $this
     */
    public function setSmsVerifyId(int $smsVerifyId): SmsproInterface;

    /**
     * Get Mobile Number
     *
     * @return string|null
     */
    public function getMobileNumber(): ?string;

    /**
     * Set Mobile Number
     *
     * @var string $mobileNumber
     * @return $this
     */
    public function setMobileNumber(string $mobileNumber): SmsproInterface;

    /**
     * Get Otp
     *
     * @return string|null
     */
    public function getOtp(): ?string;

    /**
     * Set Otp
     *
     * @var string $otp
     * @return $this
     */
    public function setOtp(string $otp): SmsproInterface;

    /**
     * Get Isverify
     *
     * @return bool|null
     */
    public function getIsverify(): ?bool;

    /**
     * Set Isverify
     *
     * @var bool $isverify
     * @return $this
     */
    public function setIsverify(bool $isverify): SmsproInterface;

    /**
     * Get Customer Id
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set Customer Id
     *
     * @var int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): SmsproInterface;

    /**
     * Get Date Created
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Date Created
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): SmsproInterface;

    /**
     * Get Date Updated
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set Date Updated
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): SmsproInterface;
}
