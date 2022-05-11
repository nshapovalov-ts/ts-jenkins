<?php
namespace Magecomp\Smspro\Observer\Customer;

use Magento\Framework\Event\ObserverInterface;

class InvoiceSaveObserver implements ObserverInterface
{
    protected $helperapi;
    protected $helperinvoice;
    protected $emailfilter;
    protected $customerFactory;

    public function __construct(
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magecomp\Smspro\Helper\Invoice $helperinvoice,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerFactory $customerFactory)
    {
        $this->helperapi = $helperapi;
        $this->helperinvoice = $helperinvoice;
        $this->emailfilter = $filter;
        $this->customerFactory = $customerFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $invoice    = $observer->getInvoice();
        $order      = $invoice->getOrder();

        if(!$this->helperinvoice->isEnabledSmspro($order->getStoreId()))
            return $this;

        if($invoice)
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
                    'invoice' => $invoice,
                    'customer' => $customer,
                    'invoice_total' => $order->formatPriceTxt($invoice->getGrandTotal()),
                    'mobilenumber' => $mobilenumber
                ]);
            }
            else
            {
                $this->emailfilter->setVariables([
                    'order' => $order,
                    'invoice' => $invoice,
                    'invoice_total' => $order->formatPriceTxt($invoice->getGrandTotal()),
                    'mobilenumber' => $mobilenumber
                ]);
            }

            if ($this->helperinvoice->isInvoiceNotificationForUser($order->getStoreId()))
            {
                $message = $this->helperinvoice->getInvoiceNotificationUserTemplate($order->getStoreId());
                $dltid = $this->helperinvoice->getInvoiceNotificationDltid($order->getStoreId());

                $finalmessage = $this->emailfilter->filter($message);
                $this->helperapi->callApiUrl($mobilenumber,$finalmessage,$dltid);
            }

            if($this->helperinvoice->isInvoiceNotificationForAdmin($order->getStoreId()) && $this->helperinvoice->getAdminNumber($order->getStoreId()))
            {
                $message = $this->helperinvoice->getInvoiceNotificationForAdminTemplate($order->getStoreId());
                $dltid = $this->helperinvoice->getInvoiceNotificationForAdminDltid($order->getStoreId());
                $finalmessage = $this->emailfilter->filter($message);
                $this->helperapi->callApiUrl($this->helperinvoice->getAdminNumber($order->getStoreId()),$finalmessage,$dltid);
            }
        }
        return $this;
    }
}
