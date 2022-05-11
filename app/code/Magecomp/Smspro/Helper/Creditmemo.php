<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Creditmemo extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_CREDITMEMO_NOTIFICATION = 'usertemplate/usercreditmemo/enable';
    const SMS_CUSTOMER_CREDITMEMO_NOTIFICATION_TEMPLATE = 'usertemplate/usercreditmemo/template';
    const SMS_CUSTOMER_CREDITMEMO_NOTIFICATION_DLTID = 'usertemplate/usercreditmemo/dltid';


    //ADMIN TEMPLATE
    const SMS_IS_ADMIN_CREDITMEMO_NOTIFICATION = 'admintemplate/admincreditmemo/enable';
    const SMS_ADMIN_CREDITMEMO_NOTIFICATION_TEMPLATE = 'admintemplate/admincreditmemo/template';
    const SMS_ADMIN_CREDITMEMO_DLTID = 'admintemplate/admincreditmemo/dltid';


    public function isCreditmemoNotificationForUser($storeID) {
        return  $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_CREDITMEMO_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function getCreditmemoNotificationUserTemplate($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CREDITMEMO_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }
    public function getCreditmemoNotificationUserDltid($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CREDITMEMO_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function isCreditmemoNotificationForAdmin($storeID)
    {
        return $this->scopeConfig->getValue(self::SMS_IS_ADMIN_CREDITMEMO_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function getCreditmemoNotificationForAdminTemplate($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_ADMIN_CREDITMEMO_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }
    public function getCreditmemoNotificationForAdminDltid($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_ADMIN_CREDITMEMO_DLTID,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }
}
