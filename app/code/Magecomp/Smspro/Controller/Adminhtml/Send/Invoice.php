<?php
/**
 * Magento Magecomp_Smspro extension
 *
 * @category   Magecomp
 * @package    Magecomp_Smspro
 * @author     Magecomp
 */

namespace Magecomp\Smspro\Controller\Adminhtml\Send;

use Magento\Backend\Model\Session\Quote as BackendModelSession;

class Invoice extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $helperapi;
    protected $backendModelSession;
    protected $customerFactory;
    protected $emailfilter;
    protected $helperinvoice;
    protected $orderRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        BackendModelSession $backendModelSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Email\Model\Template\Filter $filter,
        \Magecomp\Smspro\Helper\Invoice $helperinvoice,
        \Magento\Sales\Model\Order\Invoice $invoice,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->helperinvoice = $helperinvoice;
        $this->helperapi = $helperapi;
        $this->backendModelSession = $backendModelSession;
        $this->customerFactory = $customerFactory;
        $this->emailfilter = $filter;
        $this->invoice = $invoice;
        $this->orderRepository = $orderRepository;
    }

    public function execute()
    {

        $invoiceid = $this->getRequest()->getParam('invoice_id');
        try {

            $invoice = $this->invoice->load($invoiceid);
            $order = $this->orderRepository->get($invoice->getOrderId());
            $customer = $this->customerFactory->create()->load($order->getCustomerId());
            $billingAddress = $order->getBillingAddress();

            $mobilenumber = $billingAddress->getTelephone();
            $mobile = $customer->getMobilenumber();

            if ($mobile != '' && $mobile != null) {
                $mobilenumber = $mobile;
            }

            $this->emailfilter->setVariables([
                'invoice' => $invoice,
                'order' => $order,
                'customer' => $customer,
                'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                'mobilenumber' => $mobilenumber
            ]);

            $storeId = $this->getRequest()->getParam('store');
            if(empty($storeId)){
                $storeId = $order->getStoreId();
            }      
            $message = $this->helperinvoice->getInvoiceNotificationUserTemplate($storeId);
            $dltid = $this->helperinvoice->getInvoiceNotificationDltid($storeId);
            $finalmessage = $this->emailfilter->filter($message);
            $apiResponse = $this->helperapi->callApiUrl($mobilenumber, $finalmessage,$dltid);

            if ($apiResponse === true) {
                $this->getMessageManager()->addSuccess("SMS Sent Successfully to the Customer Mobile : " . $mobilenumber);
            } else {
                $this->getMessageManager()->addError("Something Went Wrong While sending SMS");
            }

            $this->_redirect("sales/invoice/view/invoice_id/" . $invoiceid, ['store' => $storeId]);
            return;
        } catch (\Exception $e) {
            $this->getMessageManager()->addError("There is some Technical problem, Please tray again");
            $storeId = $this->getRequest()->getParam('store');
            $this->_redirect("sales/invoice/view/invoice_id/" . $invoiceid, ['store' => $storeId]);
            return;
        }

    }

    protected function _isAllowed()
    {
        return true;
    }
}
