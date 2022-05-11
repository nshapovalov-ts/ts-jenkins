<?php 
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;

class Shipmenttrack extends \Magecomp\Smspro\Helper\Data
{
    // USER TEMPLATE
    const SMS_IS_CUSTOMER_SHIPMENT_NOTIFICATION = 'usertemplate/usershipmenttrack/enable';
    const SMS_CUSTOMER_SHIPMENT_NOTIFICATION_TEMPLATE = 'usertemplate/usershipmenttrack/template';
    const SMS_CUSTOMER_SHIPMENT_TRACKING_NUMBER = 'usertemplate/usershipmenttrack/trackingnumber';
    const SMS_CUSTOMER_SHIPMENT_TRACKING_TITLE = 'usertemplate/usershipmenttrack/trackingtitle';
    const SMS_CUSTOMER_SHIPMENT_TRACKING_URL = 'usertemplate/usershipmenttrack/trackinglink';
    const SMS_CUSTOMER_SHIPMENT_TRACKING_DLTID = 'usertemplate/usershipmenttrack/dltid';


    public function isShipmentNotificationForUser($storeID) {
        return $this->isEnabled() && $this->scopeConfig->getValue(self::SMS_IS_CUSTOMER_SHIPMENT_NOTIFICATION,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function getShipmentNotificationUserDltid($storeID)
    {
       
    return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_TRACKING_DLTID,
            ScopeInterface::SCOPE_STORE,
            $storeID);
       
    }
    public function getShipmentNotificationUserTemplate($storeID)
    {
       
    return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_NOTIFICATION_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeID);
       
    }
    public function getShipmentTrackingNumberLabel($storeID)
    {
      
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_TRACKING_NUMBER,
                ScopeInterface::SCOPE_STORE,
                $storeID);
       
    }
    public function getShipmentTrackingTitleLabel($storeID)
    {
            return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_TRACKING_TITLE,
                ScopeInterface::SCOPE_STORE,
                $storeID);
  
    }
    public function getShipmentTrackingLink($storeID)
    {
    return  $this->scopeConfig->getValue(self::SMS_CUSTOMER_SHIPMENT_TRACKING_URL,
                ScopeInterface::SCOPE_STORE,
                $storeID);
    }

}