<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class ApprovalContext extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * ApprovalContext constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Http\Context $httpContext
    ) {
        $this->httpContext = $httpContext;
        parent::__construct($context);
    }

    public function checkIsApproval()
    {
        return $this->httpContext->getValue(\Retailplace\CustomerAccount\Model\ApprovalContext::APPROVAL_CONTEXT);
    }
}
