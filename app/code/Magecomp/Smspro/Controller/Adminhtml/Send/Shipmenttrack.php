<?php
/**
 * Magento Magecomp_Smspro extension
 *
 * @category   Magecomp
 * @package    Magecomp_Smspro
 * @author     Magecomp
 */

namespace Magecomp\Smspro\Controller\Adminhtml\Send;


class Shipmenttrack extends \Magento\Backend\App\Action
{
    protected $resultRedirect;
    protected $helperapi;
    protected $customerFactory;
    protected $emailfilter;
    protected $helpershipmenttrack;
    protected $orderRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magecomp\Smspro\Helper\Apicall $helperapi,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Email\Model\Template\Filter $filter,
        \Magecomp\Smspro\Helper\Shipmenttrack $helpershipmenttrack,
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirect = $result;
        $this->helpershipmenttrack = $helpershipmenttrack;
        $this->helperapi = $helperapi;
        $this->customerFactory = $customerFactory;
        $this->emailfilter = $filter;
        $this->shipment = $shipment;
        $this->orderRepository = $orderRepository;
    }

    public function execute()
    {

        $shipmentid = $this->getRequest()->getParam('shipment_id');
        try {

            $shipment = $this->shipment->load($shipmentid);
            $order = $this->orderRepository->get($shipment->getOrderId());
            $storeId = $this->getRequest()->getParam('store');
            if(empty($storeId)){
                $storeId = $order->getStoreId();
            }     
            $customer = $this->customerFactory->create()->load($order->getCustomerId());
            $billingAddress = $order->getBillingAddress();

            $mobilenumber = $billingAddress->getTelephone();
            $mobile = $customer->getMobilenumber();

            if ($mobile != '' && $mobile != null) {
                $mobilenumber = $mobile;
            }
            $tracksCollection = $order->getTracksCollection();
            $trackhtml = "";
            $trackNumber = "";
            $trackCarrierName = "";
            foreach ($tracksCollection->getItems() as $track) {
                $trackNumber = $track->getTrackNumber();
                $trackCarrierName = $track->getTitle();
                break;
            }
            $trackurl = $this->helpershipmenttrack->getShipmentTrackingLink($storeId);
            $this->emailfilter->setVariables([
                'shipment' => $shipment,
                'order' => $order,
                'customer' => $customer,
                'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                'mobilenumber' => $mobilenumber,
                'trackinginfo' => $trackhtml,
                'trackingnumber' => $trackNumber,
                'carriername' => $trackCarrierName,
                'trackurl' => $trackurl
            ]);

            $message = $this->helpershipmenttrack->getShipmentNotificationUserTemplate($storeId);
            $dltid = $this->helpershipmenttrack->getShipmentNotificationUserDltid($storeId);
            $finalmessage = $this->emailfilter->filter($message);

            $apiResponse = $this->helperapi->callApiUrl($mobilenumber, $finalmessage , $dltid);
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