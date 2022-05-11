<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Holdorder extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_HOLDORDER_NOTIFICATION = 'usertemplate/userholdorder/enable';
    const SMS_CUSTOMER_HOLDORDER_NOTIFICATION_TEMPLATE = 'usertemplate/userholdorder/template';
    const SMS_CUSTOMER_HOLDORDER_NOTIFICATION_DLTID = 'usertemplate/userholdorder/dltid';

	public function isHoldOrderNotificationForUser($storeId) {
        return $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_HOLDORDER_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
                $storeId);
    }

    public function getHoldOrderNotificationUserTemplate($storeId)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_HOLDORDER_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
                $storeId);
    }
    public function getHoldOrderNotificationUserDltid($storeId)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_HOLDORDER_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE,
                $storeId);
    }


}
