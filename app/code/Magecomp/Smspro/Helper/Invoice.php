<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Invoice extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_INVOICE_NOTIFICATION = 'usertemplate/userinvoice/enable';
    const SMS_CUSTOMER_INVOICE_NOTIFICATION_TEMPLATE = 'usertemplate/userinvoice/template';
    const SMS_CUSTOMER_INVOICE_NOTIFICATION_DLTID = 'usertemplate/userinvoice/dltid';


    //ADMIN TEMPLATE
    const SMS_IS_ADMIN_INVOICE_NOTIFICATION = 'admintemplate/admininvoice/enable';
    const SMS_ADMIN_INVOICE_NOTIFICATION_TEMPLATE = 'admintemplate/admininvoice/template';
    const SMS_ADMIN_INVOICE_NOTIFICATION_DLTID = 'admintemplate/admininvoice/dltid';


    public function isInvoiceNotificationForUser($storeID) {
        return $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_INVOICE_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function getInvoiceNotificationUserTemplate($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_INVOICE_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE, $storeID);
    }
    public function getInvoiceNotificationDltid($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_INVOICE_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE, $storeID);
    }


    public function isInvoiceNotificationForAdmin($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_IS_ADMIN_INVOICE_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function getInvoiceNotificationForAdminTemplate($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_ADMIN_INVOICE_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }
    public function getInvoiceNotificationForAdminDltid($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_ADMIN_INVOICE_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }
}
