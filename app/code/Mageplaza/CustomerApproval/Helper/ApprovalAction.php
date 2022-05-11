<?php declare(strict_types=1);

namespace Mageplaza\CustomerApproval\Helper;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\NoSuchEntityException;
use Mageplaza\CustomerApproval\Model\Config\Source\AttributeOptions;
use Mageplaza\CustomerApproval\Model\Config\Source\AutoApprovedStatusOptions;
use Mageplaza\CustomerApproval\Model\Config\Source\TypeAction;

class ApprovalAction extends Data
{
    const AUTO_APPROVED_STATUS = 'is_auto_approved_status';

    /**
     * @param $customerId
     * @param $conditionallyApproved
     * @param string $typeAction
     * @return Customer
     * @throws NoSuchEntityException
     */
    public function approvalByCustomerId($customerId, $conditionallyApproved = false, $typeAction = TypeAction::OTHER)
    {
        $customer = $this->customerRegistry->retrieve($customerId);
        $this->approvalStatusAction($customer, AttributeOptions::APPROVED, $conditionallyApproved);
        // send email
        if ((!$this->getAutoApproveConfig() && !$this->isAdmin()) || $typeAction != TypeAction::OTHER) {
            $this->emailApprovalAction($customer, 'approve');
        }
        return $customer;
    }

    /**
     * @param int|Customer $customer
     * @param string $typeApproval
     * @param bool $conditionallyApproved
     * @throws NoSuchEntityException
     */
    public function approvalStatusAction($customer, $typeApproval, $conditionallyApproved = false)
    {
        if (is_int($customer)) {
            $customer = $this->customerRegistry->retrieve($customer);
        }

        if (!$customer instanceof Customer) {
            throw new NoSuchEntityException(__('Customer does not exist.'));
        }

        $customerData = $customer->getDataModel();
        $attribute    = $customerData->getCustomAttribute('is_approved');
        if ($attribute) {
            $needUpdate = false;
            $customerData->setId($customer->getId());
            if ($attribute->getValue() != $typeApproval) {
                $customerData->setCustomAttribute('is_approved', $typeApproval);
                $needUpdate = true;
            }
            $autoApprovedStatus = $customerData->getCustomAttribute(self::AUTO_APPROVED_STATUS);
            if ($autoApprovedStatus) {
                if ($conditionallyApproved) {
                    $customerData->setCustomAttribute(self::AUTO_APPROVED_STATUS, AutoApprovedStatusOptions::CONDITIONALLY_APPROVED);
                } else {
                    $customerData->setCustomAttribute(self::AUTO_APPROVED_STATUS, AutoApprovedStatusOptions::APPROVED);
                }
                $needUpdate = true;
            }
            if ($needUpdate) {
                $customer->updateData($customerData);
                $customer->save();
            }
        }
    }
}
