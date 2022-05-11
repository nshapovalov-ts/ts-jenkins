<?php 
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Contact extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_CONTACT_NOTIFICATION = 'usertemplate/usercontactus/enable';
    const SMS_CUSTOMER_CONTACT_NOTIFICATION_TEMPLATE = 'usertemplate/usercontactus/template';
    const SMS_CUSTOMER_CONTACT_NOTIFICATION_DLTID = 'usertemplate/usercontactus/dltid';


	//ADMIN TEMPLATE
    const SMS_IS_ADMIN_CONTACT_NOTIFICATION = 'admintemplate/admincontactus/enable';
    const SMS_ADMIN_CONTACT_NOTIFICATION_TEMPLATE = 'admintemplate/admincontactus/template';
    const SMS_ADMIN_CONTACT_NOTIFICATION_DLTID = 'admintemplate/admincontactus/dltid';


	public function isContactNotificationForUser() {
        return $this->isEnabled() && $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_CONTACT_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function getContactNotificationUserTemplate()
    {
        if($this->isEnabled())
        {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CONTACT_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
        }
    }
    public function getContactNotificationUserDltid()
    {
        if($this->isEnabled())
        {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CONTACT_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
        }
    }

    public function isContactNotificationForAdmin()
    {
        return $this->isEnabled() && $this->scopeConfig->getValue(self::SMS_IS_ADMIN_CONTACT_NOTIFICATION,
                ScopeInterface::SCOPE_STORE,
                $this->getStoreid());
    }

    public function getContactNotificationForAdminTemplate()
    {
        if($this->isEnabled())
        {
            return  $this->scopeConfig->getValue(self::SMS_ADMIN_CONTACT_NOTIFICATION_TEMPLATE,
                ScopeInterface::SCOPE_STORE,
                $this->getStoreid());
        }
    }
    public function getContactNotificationForAdminDltid()
    {
        if($this->isEnabled())
        {
            return  $this->scopeConfig->getValue(self::SMS_ADMIN_CONTACT_NOTIFICATION_DLTID,
                ScopeInterface::SCOPE_STORE,
                $this->getStoreid());
        }
    }
}