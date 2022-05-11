<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Order extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_ORDER_NOTIFICATION = 'usertemplate/userorderplace/enable';
    const SMS_CUSTOMER_ORDER_NOTIFICATION_TEMPLATE = 'usertemplate/userorderplace/template';
    const SMS_CUSTOMER_ORDER_NOTIFICATION_DLTID = 'usertemplate/userorderplace/dltid';


	//ADMIN TEMPLATE
    const SMS_IS_ADMIN_ORDER_NOTIFICATION = 'admintemplate/adminorderplace/enable';
    const SMS_ADMIN_ORDER_NOTIFICATION_TEMPLATE = 'admintemplate/adminorderplace/template';
    const SMS_ADMIN_ORDER_NOTIFICATION_DLTID = 'admintemplate/adminorderplace/dltid';


	public function isOrderNotificationForUser($storeId) {
        return $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_ORDER_NOTIFICATION,
            ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getOrderNotificationUserTemplate($storeId)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_ORDER_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE, $storeId);
    }
    public function getOrderNotificationUserDltid($storeId)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_ORDER_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isOrderNotificationForAdmin($storeId)
    {
        return  $this->scopeConfig->getValue(self::SMS_IS_ADMIN_ORDER_NOTIFICATION,
                ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getOrderNotificationForAdminTemplate($storeId)
    {
            return  $this->scopeConfig->getValue(self::SMS_ADMIN_ORDER_NOTIFICATION_TEMPLATE,
                ScopeInterface::SCOPE_STORE, $storeId);
    }
     public function getOrderNotificationForAdminDltid($storeId)
    {
            return  $this->scopeConfig->getValue(self::SMS_ADMIN_ORDER_NOTIFICATION_DLTID,
                ScopeInterface::SCOPE_STORE, $storeId);
    }
}
