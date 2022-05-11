<?php

namespace Retailplace\Theme\Block\Html;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Header as HeaderBlock;
use Magento\Store\Model\ScopeInterface;

class Header extends HeaderBlock
{
    /**
     * @type string
     */
    const XML_PATH_SHOW_INVOICES_ENABLE = 'tradesquare_invoices/invoices/enable';

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @param Context $context
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
    }

    /**
     * Check is Customer Logged In
     *
     * @return int
     */
    public function isCustomerLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * @return int
     */
    public function getMessageNotificationInterval()
    {
        return 15; //todo load from config
    }

    /**
     * Is Show Invoices
     *
     * @return bool
     */
    public function isShowInvoices(): bool
    {
        return (bool)$this->_scopeConfig->getValue(
            self::XML_PATH_SHOW_INVOICES_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
