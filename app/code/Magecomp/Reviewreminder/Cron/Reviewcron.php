<?php

namespace Magecomp\Reviewreminder\Cron;

use Magecomp\Reviewreminder\Helper\Data;
use Magecomp\Smspro\Helper\Apicall;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Email\Model\Template\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Module\Dir\Reader;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Reviewcron
{
    protected $_request;
    protected $_transportBuilder;
    protected $_storeManager;
    protected $orderRepository;
    protected $orderModel;
    protected $searchCriteriaBuilder;
    protected $productModel;
    protected $reviewHelper;
    protected $moduleReader;
    protected $emailfilter;
    protected $customerFactory;

    public function __construct(
        Http $request,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        OrderInterface $orderModel,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        Product $productModel,
        Data $reviewHelper,
        Reader $moduleReader,
        Filter $filter,
        CustomerFactory $customerFactory,
        Apicall $helperapi
    )
    {
        $this->_request = $request;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->orderModel = $orderModel;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productModel = $productModel;
        $this->reviewHelper = $reviewHelper;
        $this->moduleReader = $moduleReader;
        $this->emailfilter = $filter;
        $this->customerFactory = $customerFactory;
        $this->helperapi = $helperapi;
    }

    public function execute()
    {
        try {
            if ($this->reviewHelper->IsActive() && $this->reviewHelper->getReviewTypes() != 0) {

                $viewDir = $this->moduleReader->getModuleDir(
                    \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
                    'Magecomp_Reviewreminder');
                $viewDir .= '/frontend/web/images';

                $dayDiff = $this->reviewHelper->getDays();
                $time = time();
                $lastTime = $time - (60 * 60 * 24 * $dayDiff); // 60*60*24

                $from = date('Y-m-d 0:0:1', $lastTime);
                $to = date('Y-m-d 23:59:59', $lastTime);
                $ordersList = $this->orderModel->getCollection();
                $ordersList->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to));
                $ordersList->addAttributeToFilter('status', array('eq' => 'complete'));


                foreach ($ordersList as $order) {
                    $Cname = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
                    $customer_email = $order->getCustomerEmail();
                    $orderData = $this->orderModel->loadByIncrementId($order->getIncrementId());


                    foreach ($orderData->getAllVisibleItems() as $o_item) {
                        $product = $this->productModel->load($o_item->getProductId());
                        $productName = $product->getName();
                        $productUrl = $product->getProductUrl();
                    }
                    if ($this->reviewHelper->getReviewTypes() == 1 || $this->reviewHelper->getReviewTypes() == 3) {

                        $report = [
                            'customername' => $Cname,
                            'pname' => $productName,
                            'purl' => $productUrl,
                            'uemail' => $customer_email,
                            'img_dir' => $viewDir
                        ];

                        $postObject = new \Magento\Framework\DataObject();
                        $postObject->setData($report);

                        $store = $this->_storeManager->getStore()->getId();
                        $transport = $this->_transportBuilder->setTemplateIdentifier($this->reviewHelper->getEmailtemplate())
                            ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
                            ->setTemplateVars(['data' => $postObject])
                            ->setFrom($this->reviewHelper->getSendername())
                            ->addTo($customer_email)
                            ->getTransport();
                        $transport->sendMessage();
                    }
                    if ($this->reviewHelper->getReviewTypes() == 2 || $this->reviewHelper->getReviewTypes() == 3) {

                        $smsTemplate = $this->reviewHelper->getSMSTemplate();
                        $dltid = $this->reviewHelper->getSMSDltid();
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
                                'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                                'mobilenumber' => $mobilenumber,
                                'prod_name' => $productName,
                                'prod_url' => $productUrl,
                            ]);
                        } else {
                            $this->emailfilter->setVariables([
                                'order' => $order,
                                'order_total' => $order->formatPriceTxt($order->getGrandTotal()),
                                'mobilenumber' => $mobilenumber,
                                'prod_name' => $productName,
                                'prod_url' => $productUrl,
                            ]);
                        }
                        $finalmessage = $this->emailfilter->filter($smsTemplate);
                        $this->helperapi->callApiUrl($mobilenumber, $finalmessage,$dltid);
                    }
                }
            }
            return;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}