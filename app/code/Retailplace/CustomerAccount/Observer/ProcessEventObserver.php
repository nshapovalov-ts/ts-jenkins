<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\CustomerAccount\Model\ApprovalStatus;
use Retailplace\CustomerAccount\Model\ApprovalContext;

class ProcessEventObserver implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatus;

    /**
     * ProcessEventObserver constructor.
     * @param ApprovalStatus $approvalStatus
     * @param Context $httpContext
     * @param Session $customerSession
     */
    public function __construct(
        ApprovalStatus $approvalStatus,
        Context $httpContext,
        Session $customerSession
    ) {
        $this->approvalStatus = $approvalStatus;
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->customerSession->getCustomerId()) {
            $customerData = $this->customerSession->getCustomerData();
            if ($customerData instanceof CustomerInterface) {
                $value = (int) $this->approvalStatus->isApproved($customerData);
                $this->httpContext->setValue(
                    ApprovalContext::APPROVAL_CONTEXT,
                    $value,
                    1
                );
            }
        }
    }
}
