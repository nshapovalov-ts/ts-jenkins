<?php

/**
 * Retailplace_MobileVerification
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MobileVerification\Api;

use Retailplace\MobileVerification\Api\Data\SmsproInterface;

/**
 * Interface SmsproRepositoryInterface
 */
interface SmsproRepositoryInterface
{
    /**
     * Save Smspro
     *
     * @param \Retailplace\MobileVerification\Api\Data\SmsproInterface|\Magecomp\Smspro\Model\Smspro $smspro
     * @return \Retailplace\MobileVerification\Api\Data\SmsproInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(SmsproInterface $smspro): SmsproInterface;

    /**
     * Delete Smspro
     *
     * @param \Retailplace\MobileVerification\Api\Data\SmsproInterface $smspro
     * @return bool
     * @throws \Exception
     */
    public function delete(SmsproInterface $smspro): bool;

    /**
     * Get object
     *
     * @param string $otp
     * @return \Retailplace\MobileVerification\Api\Data\SmsproInterface
     */
    public function getByOtp(string $otp): SmsproInterface;

    /**
     * Get object
     *
     * @param int $customerId
     * @return \Retailplace\MobileVerification\Api\Data\SmsproInterface
     */
    public function getByCustomerId(int $customerId): SmsproInterface;
}
