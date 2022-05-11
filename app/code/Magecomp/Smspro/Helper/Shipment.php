<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Shipment extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_SHIPMENT_NOTIFICATION = 'usertemplate/usershipment/enable';
    const SMS_CUSTOMER_SHIPMENT_NOTIFICATION_TEMPLATE = 'usertemplate/usershipment/template';
    const SMS_CUSTOMER_SHIPMENT_NOTIFICATION_DLTID = 'usertemplate/usershipment/dltid';


    //ADMIN TEMPLATE
    const SMS_IS_ADMIN_SHIPMENT_NOTIFICATION = 'admintemplate/adminshipment/enable';
    const SMS_ADMIN_SHIPMENT_NOTIFICATION_TEMPLATE = 'admintemplate/adminshipment/template';
    const SMS_ADMIN_SHIPMENT_NOTIFICATION_DLTID = 'admintemplate/adminshipment/dltid';


    public function isShipmentNotificationForUser($storeID) {
        return $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_SHIPMENT_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function getShipmentNotificationUserTemplate($storeID)
    {

        return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeID);

    }

public function getShipmentNotificationUserDltid($storeID)
    {

        return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE,
            $storeID);

    }

    public function isShipmentNotificationForAdmin($storeID)
    {
        return  $this->scopeConfig->getValue(self::SMS_IS_ADMIN_SHIPMENT_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $storeID);
    }

    public function getShipmentNotificationForAdminTemplate($storeID)
    {

        return  $this->scopeConfig->getValue(self::SMS_ADMIN_SHIPMENT_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeID);

    }
    public function getShipmentNotificationForAdminDltid($storeID)
    {

        return  $this->scopeConfig->getValue(self::SMS_ADMIN_SHIPMENT_NOTIFICATION_DLTID,
            ScopeInterface::SCOPE_STORE,
            $storeID);

    }
}
