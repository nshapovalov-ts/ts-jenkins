<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class ShipmentSaveObserver implements ObserverInterface
{
    protected $helperapi;
    protected $helpershipment;
    protected $emailfilter;
    protected $customerFactory;
    protected $helpershipmenttrack;
    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Shipment $helpershipment,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magecomp\Smspro\Helper\Shipmenttrack $helpershipmenttrack,
        \Magento\Framework\App\RequestInterface $request)
    {
        $this->helperapi = $helperapi;
        $this->helpershipment = $helpershipment;
        $this->emailfilter = $filter;
        $this->customerFactory = $customerFactory;
        $this->helpershipmenttrack = $helpershipmenttrack;
        $this->_request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $shipment   = $observer->getShipment();
        $order      = $shipment->getOrder();
        $post = $this->_request->getPost();
        if(!$this->helpershipment->isEnabledSmspro($order->getStoreId()))
            return $this;

        if($shipment)
        {
            $carrier_title = $carrier_number  = "";

            if($post->tracking and count($post->tracking) > 0){
                $carrier_title = $post->tracking[1]['title'];
                $carrier_number = $post->tracking[1]['number'];
            }
            $billingAddress = $order->getBillingAddress();
            $mobilenumber = $billingAddress->getTelephone();

            if($order->getCustomerId() > 0)
            {
                $customer = $this->customerFactory->create()->load($order->getCustomerId());
                $mobile = $customer->getMobilenumber();
                if($mobile != '' && $mobile != null)
                {
                    $mobilenumber = $mobile;
                }

                $this->emailfilter->setVariables([
                    'order' => $order,
                    'shipment' => $shipment,
                    'customer' => $customer,
                    'mobilenumber' => $mobilenumber,
                    'shippingcarrier' => $carrier_title,
                    'trackingnumber' => $carrier_number
                ]);
            }
            else
            {
                $this->emailfilter->setVariables([
                    'order' => $order,
                    'shipment' => $shipment,
                    'mobilenumber' => $mobilenumber,
                    'shippingcarrier' => $carrier_title,
                    'trackingnumber' => $carrier_number
                ]);
            }

            if ($this->helpershipment->isShipmentNotificationForUser($order->getStoreId()))
            {
                $message = $this->helpershipment->getShipmentNotificationUserTemplate($order->getStoreId());
                $dltid = $this->helpershipment->getShipmentNotificationUserDltid($order->getStoreId());

                $finalmessage = $this->emailfilter->filter($message);
                $this->helperapi->callApiUrl($mobilenumber,$finalmessage,$dltid);
            }

            if($this->helpershipment->isShipmentNotificationForAdmin($order->getStoreId()) && $this->helpershipment->getAdminNumber($order->getStoreId()))
            {
                $message = $this->helpershipment->getShipmentNotificationForAdminTemplate($order->getStoreId());
                $dltid = $this->helpershipment->getShipmentNotificationForAdminDltid($order->getStoreId());

                $finalmessage = $this->emailfilter->filter($message);
                $this->helperapi->callApiUrl($this->helpershipment->getAdminNumber($order->getStoreId()),$finalmessage,$dltid);
            }
        }
        return $this;
    }
}
