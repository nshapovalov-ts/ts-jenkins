<?php

namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Customer\Model\Session as CustomerSession;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    // GENERAL Configuration
    const SMS_GENERAL_ENABLED = 'smspro/general/enable';
    const SMS_GENERALSECTION_BUTTONCLASS = 'smspro/generalsection/buttonclass';
    const SMS_GENERALSECTION_DEFAULTCOUNTRY = 'smspro/countryflag/defaultcountry';
    const SMS_MOBILE_MINDIGITS = 'smspro/countryflag/mindigits';
    const SMS_MOBILE_MAXDIGITS = 'smspro/countryflag/maxdigits';
    const SMS_ADMIN_MOBILE = 'admintemplate/admingeneral/mobile';

    protected $_storeManager;
    private $remoteAddress;
    protected $customerSession;
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        RemoteAddress $remoteAddress,
        CustomerSession $customerSession)
    {
        $this->_storeManager = $storeManager;
        $this->remoteAddress = $remoteAddress;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function getStoreid()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function getStoreUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::SMS_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }
    public function getMindigits()
    {
        return $this->scopeConfig->getValue(self::SMS_MOBILE_MINDIGITS,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }public function getMaxdigits()
    {
        return $this->scopeConfig->getValue(self::SMS_MOBILE_MAXDIGITS,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }
    public function isEnabledSmspro($storeId)
    {
        return $this->scopeConfig->getValue(self::SMS_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId);
    }

    public function getButtonclass()
    {
        return $this->scopeConfig->getValue(self::SMS_GENERALSECTION_BUTTONCLASS,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function getDefaultcontry()
    {
        return $this->scopeConfig->getValue(self::SMS_GENERALSECTION_DEFAULTCOUNTRY,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function checkAdminNumber($storeId)
    {
        return $this->scopeConfig->getValue(self::SMS_ADMIN_MOBILE,
            ScopeInterface::SCOPE_STORE,
            $storeId);
    }

    public function getAdminNumber($storeId)
    {
        if ($this->isEnabledSmspro($storeId) && $this->checkAdminNumber($storeId) != '' && $this->checkAdminNumber($storeId) != null) {
            return $this->checkAdminNumber($storeId);
        }
    }
}
