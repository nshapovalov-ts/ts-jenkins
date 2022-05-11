<?php

namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class StatuschangeObserver implements ObserverInterface
{
    protected $helperapi;
    protected $holdhelperorder;
    protected $emailfilter;
    protected $customerFactory;
    protected $statusArray = ['holded', 'unholded'];


    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Holdorder $holdhelperorder,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerFactory $customerFactory)
    {
        $this->helperapi = $helperapi;
        $this->holdhelperorder = $holdhelperorder;
        $this->emailfilter = $filter;
        $this->customerFactory = $customerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$this->holdhelperorder->isEnabledSmspro($order->getStoreId()))
             return $this;
        if ($order) {
            if (!in_array($order->getState(), $this->statusArray))
                return $this;

            $billingAddress = $order->getBillingAddress();
            $mobilenumber = $billingAddress->getTelephone();

            if ($order->getCustomerId() > 0) {
                $customer = $this->customerFactory->create()->load($order->getCustomerId());
                $mobile = $customer->getMobilenumber();
                if ($mobile != '' && $mobile != null) {
                    $mobilenumber = $mobile;
                }

                $this->emailfilter->setVariables([
                    'order' => $order,
                    'customer' => $customer,
                    'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                    'mobilenumber' => $mobilenumber,
                    'status' => $order->getStatus()
                ]);
            } else {
                $this->emailfilter->setVariables([
                    'order' => $order,
                    'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                    'mobilenumber' => $mobilenumber,
                    'status' => $order->getStatus()
                ]);
            }
            if (in_array("holded", $this->statusArray)) {
                if ($this->holdhelperorder->isHoldOrderNotificationForUser($order->getStoreId())) {
                    $message = $this->holdhelperorder->getHoldOrderNotificationUserTemplate($order->getStoreId());
                    $dltid = $this->holdhelperorder->getHoldOrderNotificationUserDltid($order->getStoreId());

                    $finalmessage = $this->emailfilter->filter($message);
                    $this->helperapi->callApiUrl($mobilenumber, $finalmessage,$dltid);
                }
            }
        }
        return $this;
    }
}
