<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Cancelorder extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_CANCELORDER_NOTIFICATION = 'usertemplate/usercancelorder/enable';
    const SMS_CUSTOMER_CANCELORDER_NOTIFICATION_TEMPLATE = 'usertemplate/usercancelorder/template';
    const SMS_CUSTOMER_CANCELORDER_NOTIFICATION_DLTID = 'usertemplate/usercancelorder/dltid';


	public function isCancelorderNotificationForUser($storeID) {
        return $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_CANCELORDER_NOTIFICATION,
            ScopeInterface::SCOPE_STORE, $storeID);
    }

    public function getCancelorderNotificationUserTemplate($storeID)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CANCELORDER_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE, $storeID);
    }
    public function getCancelorderNotificationUserDltid($storeID)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CANCELORDER_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE, $storeID);
    }


}
