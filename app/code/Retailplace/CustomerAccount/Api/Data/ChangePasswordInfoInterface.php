<?php

namespace Retailplace\CustomerAccount\Api\Data;

interface ChangePasswordInfoInterface
{
    /**#@+
     * Constants
     */
    const CUSTOMER = 'customer';
    const CHANGE_PASSWORD_STATUS = 'change_password_status';

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer();

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return $this
     */
    public function setCustomer($customer);

    /**
     * Change password status
     *
     * @return bool
     */
    public function getChangePasswordStatus();

    /**
     * @param bool $status
     * @return $this
     */
    public function setChangePasswordStatus($status);
}
