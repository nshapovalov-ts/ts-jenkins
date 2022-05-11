<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class CreditmemoSaveObserver implements ObserverInterface
{
    protected $helperapi;
    protected $helpercreditmemo;
    protected $emailfilter;
    protected $customerFactory;

    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Creditmemo $helpercreditmemo,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerFactory $customerFactory)
    {
        $this->helperapi = $helperapi;
        $this->helpercreditmemo = $helpercreditmemo;
        $this->emailfilter = $filter;
        $this->customerFactory = $customerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {


        $creditmemo = $observer->getCreditmemo();
        $order      = $creditmemo->getOrder();
        if(!$this->helpercreditmemo->isEnabledSmspro($order->getStoreId()))
            return $this;
        if($creditmemo)
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
                    'creditmemo' => $creditmemo,
                    'customer' => $customer,
                    'mobilenumber' => $mobilenumber
                ]);
            }
            else
            {
                $this->emailfilter->setVariables([
                    'order' => $order,
                    'creditmemo' => $creditmemo,
                    'mobilenumber' => $mobilenumber
                ]);
            }

            if ($this->helpercreditmemo->isCreditmemoNotificationForUser($order->getStoreId()))
            {
                $message = $this->helpercreditmemo->getCreditmemoNotificationUserTemplate($order->getStoreId());
                $finalmessage = $this->emailfilter->filter($message);
                $dltid=$this->helpercreditmemo->getCreditmemoNotificationUserDltid($order->getStoreId());
                $this->helperapi->callApiUrl($mobilenumber,$finalmessage,$dltid);
            }

            if($this->helpercreditmemo->isCreditmemoNotificationForAdmin($order->getStoreId()) && $this->helpercreditmemo->getAdminNumber($order->getStoreId()))
            {
                $message = $this->helpercreditmemo->getCreditmemoNotificationForAdminTemplate($order->getStoreId());
                $dltid = $this->helpercreditmemo->getCreditmemoNotificationForAdminDltid($order->getStoreId());

                $finalmessage = $this->emailfilter->filter($message);
                $this->helperapi->callApiUrl($this->helpercreditmemo->getAdminNumber($order->getStoreId()),$finalmessage,$dltid);
            }
        }
        return $this;
    }
}
