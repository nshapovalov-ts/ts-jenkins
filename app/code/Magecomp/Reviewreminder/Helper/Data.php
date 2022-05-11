<?php

namespace Magecomp\Reviewreminder\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const SMS_GENERAL_ENABLED = 'smspro/general/enable';
    protected $scopeConfig;
    protected $_storeManager;

    public function __construct( Context $context,
                                 \Magento\Store\Model\StoreManagerInterface $storeManager )
    {
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function IsActive()
    {
        return $this->scopeConfig->getValue(self::SMS_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getReviewTypes()
    {
        return $this->scopeConfig->getValue('usertemplate/reviewreminder/reviewremindertypes',
            ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getDays()
    {
        return $this->scopeConfig->getValue('usertemplate/reviewreminder/reviewdays',
            ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getEmailtemplate()
    {
        return $this->scopeConfig->getValue('usertemplate/reviewreminder/templateemail',
            ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getSendername()
    {
        return $this->scopeConfig->getValue('reviewreminer/general/sender_email_identity',
            ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getSMSTemplate()
    {
        return $this->scopeConfig->getValue('usertemplate/reviewreminder/reviewsmstext',
            ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

    public function getSMSDltid()
    {
        return $this->scopeConfig->getValue('usertemplate/reviewreminder/dltid',
            ScopeInterface::SCOPE_STORE, $this->getStoreId());
    }

}