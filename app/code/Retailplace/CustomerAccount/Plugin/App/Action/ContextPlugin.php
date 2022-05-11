<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Plugin\App\Action;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\CustomerAccount\Model\ApprovalStatus;
use Retailplace\CustomerAccount\Model\ApprovalContext;

class ContextPlugin
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
     * ContextPlugin constructor.
     * @param ApprovalStatus $approvalStatus
     * @param Session $customerSession
     * @param Context $httpContext
     */
    public function __construct(
        ApprovalStatus $approvalStatus,
        Session $customerSession,
        Context $httpContext
    ) {
        $this->approvalStatus = $approvalStatus;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * @param ActionInterface $subject
     * @param RequestInterface $request
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function beforeDispatch(
        ActionInterface $subject,
        RequestInterface $request
    ) {
        if ($this->customerSession->getCustomerId()) {
            $customerData = $this->customerSession->getCustomerData();
            if ($customerData instanceof CustomerInterface) {
                $value = (int) $this->approvalStatus->isApproved($customerData);
                $this->httpContext->setValue(
                    ApprovalContext::APPROVAL_CONTEXT,
                    $value,
                    ApprovalContext::DEFAULT_CONTEXT_VALUE
                );
            }
        } else {
            $this->httpContext->setValue(
                ApprovalContext::APPROVAL_CONTEXT,
                ApprovalContext::DEFAULT_CONTEXT_VALUE,
                ApprovalContext::DEFAULT_CONTEXT_VALUE
            );
        }
    }
}
