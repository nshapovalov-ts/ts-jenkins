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

class Order extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $helperapi;
    protected $backendModelSession;
    protected $orderRepository;
    protected $customerFactory;
    protected $emailfilter;
    protected $helperorder;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        BackendModelSession $backendModelSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Email\Model\Template\Filter $filter,
        \Magecomp\Smspro\Helper\Order $helperorder
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->helperorder = $helperorder;
        $this->helperapi = $helperapi;
        $this->backendModelSession = $backendModelSession;
        $this->orderRepository = $orderRepository;
        $this->customerFactory = $customerFactory;
        $this->emailfilter = $filter;
    }

    public function execute()
    {

        $orderid = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($orderid);
            $customer = $this->customerFactory->create()->load($order->getCustomerId());
            $billingAddress = $order->getBillingAddress();

            $mobilenumber = $billingAddress->getTelephone();
            $mobile = $customer->getMobilenumber();

            if ($mobile != '' && $mobile != null) {
                $mobilenumber = $mobile;
            }
            $this->emailfilter->setVariables([
                'order' => $order,
                'customer' => $customer,
                'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                'mobilenumber' => $mobilenumber
            ]);
            $storeId = $this->getRequest()->getParam('store');
            if(empty($storeId)){
                $storeId = $order->getStoreId();
            }     
            $message = $this->helperorder->getOrderNotificationUserTemplate($storeId);
            $dltid = $this->helperorder->getOrderNotificationForAdminDltid($storeId);
            $finalmessage = $this->emailfilter->filter($message);
            $apiResponse = $this->helperapi->callApiUrl($mobilenumber, $finalmessage ,$dltid);

            if ($apiResponse === true) {
                $this->getMessageManager()->addSuccess("SMS Sent Successfully to the Customer Mobile : " . $mobilenumber);
            } else {
                $this->getMessageManager()->addError("Something Went Wrong While sending SMS");
            }

            $this->_redirect("sales/order/view/order_id/" . $orderid, ['store' => $storeId]);
            return;
        } catch (\Exception $e) {
            $this->getMessageManager()->addError("There is some Technical problem, Please tray again");
            $storeId = $this->getRequest()->getParam('store');
            $this->_redirect("sales/order/view/order_id/" . $orderid, ['store' => $storeId]);
            return;
        }

    }

    protected function _isAllowed()
    {
        return true;
    }
}
