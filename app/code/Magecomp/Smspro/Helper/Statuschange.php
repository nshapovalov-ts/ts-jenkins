<?php 
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Statuschange extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_CANCELORDER_NOTIFICATION = 'usertemplate/usercancelorder/enable';
    const SMS_CUSTOMER_CANCELORDER_NOTIFICATION_TEMPLATE = 'usertemplate/usercancelorder/template';

	public function isCancelorderNotificationForUser() {
        return $this->isEnabled() && $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_CANCELORDER_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function getCancelorderNotificationUserTemplate()
    {
        if($this->isEnabled())
        {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_CANCELORDER_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
        }
    }


}