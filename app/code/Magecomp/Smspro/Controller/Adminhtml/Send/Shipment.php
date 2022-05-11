<?php
namespace Magecomp\Smspro\Controller\Adminhtml\Send;


class Shipment extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $helperapi;
    protected $customerFactory;
    protected $emailfilter;
    protected $helpershipment;
    protected $orderRepository;
    protected $_order;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Email\Model\Template\Filter $filter,
        \Magecomp\Smspro\Helper\Shipment $helpershipment,
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order $order
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->helpershipment = $helpershipment;
        $this->helperapi = $helperapi;
        $this->customerFactory = $customerFactory;
        $this->emailfilter = $filter;
        $this->shipment = $shipment;
        $this->orderRepository = $orderRepository;
        $this->_order = $order;
    }

    public function execute()
    {
        $shipmentid = $this->getRequest()->getParam('shipment_id');
        try {

            $shipment = $this->shipment->load($shipmentid);
            $order = $this->orderRepository->get($shipment->getOrderId());
           
            $billingAddress = $order->getBillingAddress();
            $order =  $this->_order->load($shipment->getOrderId());
            $tracksCollection = $order->getTracksCollection();
            foreach ($tracksCollection->getItems() as $track) {
                $trackNumbers[] = $track->getTrackNumber();
                $trackTitle[] = $track->getTitle();
            }
            $mobilenumber = $billingAddress->getTelephone();
            $tracktital1="";
            $trackNumbers1="";
            if(!empty($trackTitle) || !empty($trackNumbers)){
                $tracktital1=$trackTitle[0];
                $trackNumbers1=$trackNumbers[0];
            }
            if($order->getCustomerId() > 0){
                 $customer = $this->customerFactory->create()->load($order->getCustomerId());
                     $mobile = $customer->getMobilenumber();
                 if ($mobile != '' && $mobile != null) {
                        $mobilenumber = $mobile;
                 }
                $this->emailfilter->setVariables([
                    'shipment' => $shipment,
                    'order' => $order,
                    'customer' => $customer,
                    'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                    'mobilenumber' => $mobilenumber,
                    'shippingcarrier' => $tracktital1,
                    'trackingnumber' => $trackNumbers1

                    ]);
            }else{
                $this->emailfilter->setVariables([
                'shipment' => $shipment,
                'order' => $order,
                'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                'mobilenumber' => $mobilenumber,
                'shippingcarrier' => $tracktital1,
                'trackingnumber' => $trackNumbers1
                ]);
            }
           
            $storeId = $this->getRequest()->getParam('store');
            if(empty($storeId)){
                $storeId = $order->getStoreId();
            }     
            $message = $this->helpershipment->getShipmentNotificationUserTemplate($storeId);
            $dltid = $this->helpershipment->getShipmentNotificationUserDltid($storeId);
            $finalmessage = $this->emailfilter->filter($message);
            $apiResponse = $this->helperapi->callApiUrl($mobilenumber, $finalmessage ,$dltid);

            if ($apiResponse === true) {
                $this->getMessageManager()->addSuccess("SMS Sent Successfully to the Customer Mobile : " . $mobilenumber);
            } else {
                $this->getMessageManager()->addError("Something Went Wrong While sending SMS");
            }
            $this->_redirect("sales/shipment/view/shipment_id/" . $shipmentid, ['store' => $storeId]);
            return;
        } catch (\Exception $e) {
            $this->getMessageManager()->addError("There is some Technical problem, Please tray again");
            $storeId = $this->getRequest()->getParam('store');
            $this->_redirect("sales/shipment/view/shipment_id/" . $shipmentid, ['store' => $storeId]);
            return;
        }

    }

    protected function _isAllowed()
    {
        return true;
    }
}
