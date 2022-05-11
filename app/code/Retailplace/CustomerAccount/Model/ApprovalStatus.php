<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Retailplace\CustomerAccount\Model\Config\Source\AttributeOptions;

class ApprovalStatus
{
    /**
     * @param CustomerInterface $customer
     * @return bool
     */
    public function isApproved(CustomerInterface $customer)
    {
        $isApproved = $customer->getCustomAttribute('is_approved');
        if ($isApproved && $isApproved->getValue() == AttributeOptions::APPROVED) {
            return true;
        }
        return false;
    }

    /**
     * @param CustomerInterface $customer
     * @return bool
     */
    public function isPending(CustomerInterface $customer)
    {
        $isApproved = $customer->getCustomAttribute('is_approved');
        if ($isApproved && $isApproved->getValue() == AttributeOptions::PENDING) {
            return true;
        }
        return false;
    }
}
