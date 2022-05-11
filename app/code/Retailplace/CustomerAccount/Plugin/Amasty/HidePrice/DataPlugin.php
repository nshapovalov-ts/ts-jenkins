<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Plugin\Amasty\HidePrice;

use Amasty\HidePrice\Helper\Data;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\CustomerAccount\Model\ApprovalStatus;

class DataPlugin
{
    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ApprovalStatus
     */
    private $approvalStatus;

    /**
     * DataPlugin constructor.
     * @param ApprovalStatus $approvalStatus
     * @param Session $customerSession
     * @param HttpContext $httpContext
     */
    public function __construct(
        ApprovalStatus $approvalStatus,
        Session $customerSession,
        HttpContext $httpContext
    ) {
        $this->approvalStatus = $approvalStatus;
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
    }

    /**
     * @param Data $subject
     * @param $result
     * @return boolean
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterCheckCustomerAttributes(
        Data $subject,
        $result
    ) {
        if (false === $result) {
            $customerData = $this->customerSession->getCustomerData();
            if ($customerData instanceof CustomerInterface) {
                if (false === $this->approvalStatus->isApproved($customerData)) {
                    $result = true;
                }
            }
        }

        return $result;
    }
}
