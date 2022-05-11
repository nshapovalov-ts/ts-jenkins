<?php

namespace Retailplace\CustomerAccount\Api;

/**
 * Interface AccountManagementInterface
 * @package Retailplace\CustomerAccount\Api
 * @api
 */
interface AccountManagementInterface
{
    /**
     * Create customer account. Perform necessary business operations like sending email.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param bool $needValidateAddress
     * @param bool $needValidateApproval
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    public function update(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
        $needValidateAddress = false,
        $needValidateApproval = false
    );

    /**
     * @param int $customerId
     * @param string $email
     * @param string $currentPassword
     * @param string $newPassword
     * @param bool $isChangeEmail
     * @param bool $isChangePassword
     * @return \Retailplace\CustomerAccount\Api\Data\ChangePasswordInfoInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Magento\Framework\Exception\InvalidEmailOrPasswordException
     */
    public function changeEmailAndPassword(
        $customerId,
        $email,
        $currentPassword,
        $newPassword,
        $isChangeEmail= false,
        $isChangePassword = false
    );
}
