<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Plugin\Layout;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\DepersonalizeChecker;
use Retailplace\CustomerAccount\Model\ApprovalContext;
use Retailplace\CustomerAccount\Model\ApprovalStatus;

class DepersonalizePlugin
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var DepersonalizeChecker
     */
    private $depersonalizeChecker;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatus;

    /**
     * @var int
     */
    protected $approvalStatusValue;

    /**
     * @param ApprovalStatus $approvalStatus
     * @param Session $customerSession
     * @param HttpContext $httpContext
     * @param DepersonalizeChecker $depersonalizeChecker
     */
    public function __construct(
        ApprovalStatus $approvalStatus,
        Session $customerSession,
        HttpContext $httpContext,
        DepersonalizeChecker $depersonalizeChecker
    ) {
        $this->approvalStatus = $approvalStatus;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->depersonalizeChecker =$depersonalizeChecker;
    }

    /**
     * @param LayoutInterface $subject
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function beforeGenerateXml(LayoutInterface $subject)
    {
        if ($this->depersonalizeChecker->checkIfDepersonalize($subject)) {
            $customerData = $this->customerSession->getCustomerData();
            $status = 0;
            if ($customerData instanceof CustomerInterface) {
                $status = (int) $this->approvalStatus->isApproved($customerData);
            }
            $this->approvalStatusValue = $status;
        }
    }

    /**
     * Change sensitive customer data if the depersonalization is needed.
     *
     * @param LayoutInterface $subject
     * @return void
     */
    public function afterGenerateElements(LayoutInterface $subject)
    {
        if ($this->depersonalizeChecker->checkIfDepersonalize($subject)) {
            $this->httpContext->setValue(ApprovalContext::APPROVAL_CONTEXT, $this->approvalStatusValue, ApprovalContext::DEFAULT_CONTEXT_VALUE);
            $this->customerSession->setApprovalStatus($this->approvalStatusValue);
        }
    }
}
