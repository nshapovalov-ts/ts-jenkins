<?php

namespace Retailplace\MiraklSellerAdditionalField\Plugin\App\Action;

use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;
use Magento\PageCache\Model\Config;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;

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
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Config
     */
    private $cacheConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Session $customerSession
     * @param Context $httpContext
     * @param Manager $moduleManager
     * @param Config $cacheConfig
     * @param Data $helper
     */
    public function __construct(
        Session $customerSession,
        Context $httpContext,
        Manager $moduleManager,
        Config $cacheConfig,
        Data $helper
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->moduleManager = $moduleManager;
        $this->cacheConfig = $cacheConfig;
        $this->helper = $helper;
    }

    /**
     * @param ActionInterface $subject
     * @param RequestInterface $request
     * @return void
     */
    public function beforeDispatch(
        ActionInterface $subject,
        RequestInterface $request
    ) {
        if (!$this->customerSession->isLoggedIn() ||
            !$this->moduleManager->isEnabled('Magento_PageCache') ||
            !$this->cacheConfig->isEnabled()) {
            return;
        }
        $this->helper->updateHttpContext();
    }
}
