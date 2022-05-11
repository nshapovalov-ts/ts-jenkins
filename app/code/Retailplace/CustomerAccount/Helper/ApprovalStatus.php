<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\CustomerAccount\Model\Config\Source\AttributeOptions;
use Retailplace\CustomerAccount\Model\Config\Source\IncompleteApplicationStatus;

class ApprovalStatus extends AbstractHelper
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * ApprovalStatus constructor.
     * @param Context $context
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        Session $customerSession
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return mixed|string
     */
    public function getAutoApprovalStatus()
    {
        if ($customerData = $this->getCustomerData()) {
            $isApproved = $customerData->getCustomAttribute('is_approved');
            $isAutoApprovedStatus = $customerData->getCustomAttribute('is_auto_approved_status');
            if ($isApproved && $isApproved->getValue() == AttributeOptions::APPROVED) {
                if ($isAutoApprovedStatus) {
                    return  $isAutoApprovedStatus->getValue();
                }
            }
        }

        return AttributeOptions::PENDING;
    }

    /**
     * @return bool
     */
    public function isApprovedStatus()
    {
        if ($customerData = $this->getCustomerData()) {
            $isApproved = $customerData->getCustomAttribute('is_approved');
            if ($isApproved && $isApproved->getValue() == AttributeOptions::APPROVED) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isIncompleteApplication()
    {
        if ($customerData = $this->getCustomerData()) {
            $incompleteApplication = $customerData->getCustomAttribute('incomplete_application');
            if ($incompleteApplication && $incompleteApplication->getValue() == IncompleteApplicationStatus::COMPLETE_APPLICATION) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return CustomerInterface|null
     */
    private function getCustomerData()
    {
        if ($this->customerSession->getCustomerId()) {
            try {
                $customerData = $this->customerSession->getCustomerData();
                if ($customerData instanceof CustomerInterface) {
                    return $customerData;
                }
            } catch (NoSuchEntityException | LocalizedException $noSuchEntityException) {
            }
        }
        return null;
    }
}
