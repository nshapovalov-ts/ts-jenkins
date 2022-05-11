<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model;

use Magento\Framework\Model\AbstractModel;
use Retailplace\CustomerAccount\Api\Data\ChangePasswordInfoInterface;

class ChangePasswordInfo extends AbstractModel implements ChangePasswordInfoInterface
{
    /**
     * @inheritdoc
     */
    public function getCustomer()
    {
        return $this->getData(self::CUSTOMER);
    }

    /**
     * @inheritdoc
     */
    public function setCustomer($customer)
    {
        return $this->setData(self::CUSTOMER, $customer);
    }

    /**
     * @inheritdoc
     */
    public function getChangePasswordStatus()
    {
        return $this->getData(self::CHANGE_PASSWORD_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setChangePasswordStatus($status)
    {
        return $this->setData(self::CHANGE_PASSWORD_STATUS, $status);
    }
}
