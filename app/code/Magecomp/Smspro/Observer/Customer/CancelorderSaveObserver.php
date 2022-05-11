<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class CancelorderSaveObserver implements ObserverInterface
{
    protected $helperapi;
    protected $helperorder;
    protected $emailfilter;
    protected $customerFactory;

    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Cancelorder $helperorder,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerFactory $customerFactory)
    {
        $this->helperapi = $helperapi;
        $this->helperorder = $helperorder;
        $this->emailfilter = $filter;
        $this->customerFactory = $customerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if(!$this->helperorder->isEnabledSmspro($order->getStoreId()) && !$this->helperorder->isCancelorderNotificationForUser($order->getStoreId()))
            return $this;
        if($order)
        {
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
                    'customer' => $customer,
                    'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                    'mobilenumber' => $mobilenumber
                ]);
            }
            else
            {
                $this->emailfilter->setVariables([
                    'order' => $order,
                    'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                    'mobilenumber' => $mobilenumber
                ]);
            }
            if ($this->helperorder->isCancelorderNotificationForUser($order->getStoreId()))
            {
                $message = $this->helperorder->getCancelorderNotificationUserTemplate($order->getStoreId());
                $dltid = $this->helperorder->getCancelorderNotificationUserDltid($order->getStoreId());
                $finalmessage = $this->emailfilter->filter($message);
                $this->helperapi->callApiUrl($mobilenumber,$finalmessage,$dltid);
            }
        }
        return $this;
    }
}
