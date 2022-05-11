<?php
/**
 * Recent Order Notification
 * @author      Trinh Doan
 * @copyright   Copyright (c) 2017 Trinh Doan
 * @package     TD_SoldNotification
 */

namespace TD\SoldNotification\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $_orderCollectionFactory;
    protected $orderRepository;
    protected $productFactory;
    protected $imageHelperFactory;
    protected $resultJsonFactory;
    protected $countryFactory;
    protected $soldNotificationHelper;
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \TD\SoldNotification\Helper\Data $soldNotificationHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->_productFactory = $productFactory;
        $this->imageHelperFactory = $imageHelperFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->countryFactory = $countryFactory;
        $this->soldNotificationHelper = $soldNotificationHelper;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);

    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $messageTemplate = $this->scopeConfig->getValue('sold_notification/design/message_template', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $useFakeOrder = $this->scopeConfig->getValue('sold_notification/order/use_fake_order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $data = array();
        if ($useFakeOrder == 1) {
            $fakeAddressArr = explode("\n", $this->scopeConfig->getValue('sold_notification/order/fake_address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            $fakeAddressKey = array_rand($fakeAddressArr, 1);
            $fakeAddress = $fakeAddressArr[$fakeAddressKey];

            $fakeProductArr = explode(",", $this->scopeConfig->getValue('sold_notification/order/fake_product', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            $fakeProductKey = array_rand($fakeProductArr, 1);
            $fakeProductId = $fakeProductArr[$fakeProductKey];

            $fakeOrderTimeArr = explode(',', $this->scopeConfig->getValue('sold_notification/order/fake_order_time', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
            $fakeOrderTimeKey = array_rand($fakeOrderTimeArr, 1);
            $fakeOrderTime = $fakeOrderTimeArr[$fakeOrderTimeKey];

            $product = $this->_productFactory->create()->load(trim($fakeProductId));
            $imageUrl = $this->imageHelperFactory->create()
                ->init($product, 'recently_compared_products_images_names_widget')->getUrl();
            $data['img'] = $imageUrl;
            $messageTemplate = str_replace("[city]", '', $messageTemplate);
            $messageTemplate = str_replace("[region]", '', $messageTemplate);
            $messageTemplate = str_replace("[country]", '', $messageTemplate);
            $messageTemplate = str_replace(',,', '', $messageTemplate);
            $messageTemplate = str_replace("[shipping_address]", $fakeAddress, $messageTemplate);
            $messageTemplate = str_replace("[product_link]", '<a target="_blank" href="' . $product->getProductUrl() . '">' . $product->getName() . '</a>', $messageTemplate);
            $messageTemplate = str_replace("[ordered_time]", '' . $fakeOrderTime . '', $messageTemplate);
            $data['message'] = $messageTemplate;
        } else {
            $limitLastOrder = $this->scopeConfig->getValue('sold_notification/order/limit_last_order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $numberOrders = $limitLastOrder ? $limitLastOrder : 5;
            $collection = $this->_orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->setPageSize($numberOrders)
                ->addOrder('entity_id');
            if ($this->scopeConfig->getValue('sold_notification/order/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                $collection->addFieldToFilter('status', ['in' => explode(',', ($this->scopeConfig->getValue('sold_notification/order/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)))]);
            }
            $itemIdArray = [];
            foreach ($collection as $order) {
                if ($this->scopeConfig->getValue('sold_notification/order/random_order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                    $OrderIdsArr[] = $order->getId();
                    foreach ($order->getAllItems() as $item) {
                        $itemIdArray[] = array("item_id" => $item->getProductId(), "order_id" => $order->getId());
                        break;
                    }
                } else {
                    $address = $order->getBillingAddress();
                    $order = $this->orderRepository->get($order->getId());
                    $country = $this->countryFactory->create()->loadByCode($address->getCountryId());
                    $messageTemplate = str_replace("[city]", $address->getCity(), $messageTemplate);
                    $messageTemplate = str_replace("[region]", $address->getRegion(), $messageTemplate);
                    $messageTemplate = str_replace("[country]", $country->getName(), $messageTemplate);

                    foreach ($order->getAllItems() as $item) {
                        $product = $this->_productFactory->create()->load($item->getProductId());
                        $imageUrl = $this->imageHelperFactory->create()
                            ->init($product, 'recently_compared_products_images_names_widget')->getUrl();
                        $data['img'] = $imageUrl;
                        $messageTemplate = str_replace("[product_link]", '<a target="_blank" href="' . $product->getProductUrl() . '">' . $product->getName() . '</a>', $messageTemplate);
                        $messageTemplate = str_replace("[ordered_time]", '' . $this->soldNotificationHelper->getTimeAgo($order->getCreatedAt()) . '', $messageTemplate);
                        $data['message'] = $messageTemplate;
                        break;
                    }
                    break;
                }
            }

            if ($this->scopeConfig->getValue('sold_notification/order/random_order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) && !empty($OrderIdsArr)) {

                $OrderIdsArrKey = array_rand($OrderIdsArr, 1);
                $itemIdArray = array_unique($itemIdArray, SORT_REGULAR);
                $itemIdRand = array_rand($itemIdArray);

                $itemOrderArray = $itemIdArray[$itemIdRand];

                //$OrderId = $OrderIdsArr[$OrderIdsArrKey];
                $OrderId = $itemOrderArray['order_id'];
                $itemId = $itemOrderArray['item_id'];
                $order = $this->orderRepository->get($OrderId);
                $address = $order->getBillingAddress();
                $country = $this->countryFactory->create()->loadByCode($address->getCountryId());

                $messageTemplate = str_replace("[city]", $address->getCity(), $messageTemplate);
                $messageTemplate = str_replace("[region]", $address->getRegion(), $messageTemplate);
                $messageTemplate = str_replace("[country]", $country->getName(), $messageTemplate);

                $product = $this->_productFactory->create()->load($itemId);
                $imageUrl = $this->imageHelperFactory->create()
                    ->init($product, 'recently_compared_products_images_names_widget')->getUrl();
                $data['img'] = $imageUrl;
                $messageTemplate = str_replace("[product_link]", '<a target="_blank" href="' . $product->getProductUrl() . '">' . $product->getName() . '</a>', $messageTemplate);
                $messageTemplate = str_replace("[ordered_time]", '' . $this->soldNotificationHelper->getTimeAgo($order->getCreatedAt()) . '', $messageTemplate);
                $data['message'] = $messageTemplate;
            }
        }
        if (!empty($data)) {
            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData($data);
        } else {
            echo('There is no product in the order');
        }

    }
}
