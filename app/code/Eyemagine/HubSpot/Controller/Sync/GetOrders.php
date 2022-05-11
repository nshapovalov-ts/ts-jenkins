<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Controller\Sync;

use Magento\Framework\App\Action\Context;
use Eyemagine\HubSpot\Controller\AbstractSync;
use Magento\Framework\Controller\Result\JsonFactory;
use Eyemagine\HubSpot\Helper\Sync as HelperSync;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;
use Exception;

/**
 * Class GetOrders
 *
 * @package Eyemagine\HubSpot\Controller\Sync
 */
class GetOrders extends AbstractSync
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollection;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helperSync,
        OrderCollection $orderCollection
    ) {
        parent::__construct($context, $resultJsonFactory);
        
        $this->helper = $helperSync;
        
        $this->orderCollection = $orderCollection;
    }

    /**
     * Get order data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        
        try {
            if (! $this->helper->authenticate()) {
                return $this->outputError($this->helper->getErrorCode(), $this->helper->getErrorMessage(), null);
            }
            $request = $this->getRequest();
            $multistore = $request->getParam('multistore', self::IS_MULTISTORE);
            $start = gmdate('Y-m-d H:i:s', $request->getParam('start', 0));
            $end = gmdate('Y-m-d H:i:s', time() - 300);
            $entityId = $request->getParam('id', '0');
            $maxperpage = $request->getParam('maxperpage', self::MAX_ORDER_PERPAGE);
            $maxAssociated = $request->getParam('maxassoc', self::MAX_ASSOC_PRODUCT_LIMIT);
            $websiteId = $this->helper->getWebsiteId();
            $storeId = $this->helper->getStoreId();
            $stores=$this->helper->getStores();
            $orderData = array();
            
            $custGroups = $this->helper->getCustomerGroups();
            $orderCollection = $this->orderCollection->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('updated_at', array(
                'from' => $start,
                'to' => $end,
                'date' => true))
                ->addFieldToFilter('entity_id', array(
                    'gt' => $entityId
                ))
                ->setOrder('updated_at', self::SORT_ORDER_ASC)
                ->setOrder('entity_id', self::SORT_ORDER_ASC)
                ->setPageSize($maxperpage);
                
            
            // only add the filter if store id > 0
            if (! ($multistore) && $storeId) {
                $orderCollection->addFieldToFilter('store_id', array(
                    'eq' => $storeId
                ));
            }
            
            foreach ($orderCollection as $order) {
                $result = $this->helper->convertAttributeData($order);
                $groupId = (int) $order->getCustomerGroupId();
                
                $result['customer_group'] = (isset($custGroups[$groupId])) ? $custGroups[$groupId] : 'Guest';
                $result['website_id']       = (isset($stores[$result['store_id']]['website_id']))?  $stores[$result['store_id']]['website_id']: $websiteId;
                $result['store_url']        = (isset($stores[$result['store_id']]['store_url']))?  $stores[$result['store_id']]['store_url']: $this->helper->getBaseUrl();
                $result['media_url']        = (isset($stores[$result['store_id']]['media_url']))?  $stores[$result['store_id']]['media_url']:$this->helper->getMediaUrl();
                $result['shipping_address'] = $this->helper->convertAttributeData($order->getShippingAddress());
                $result['billing_address'] = $this->helper->convertAttributeData($order->getBillingAddress());
                $result['comment'] = $order->getStatusHistoryCollection()->getFirstItem()->getComment();
                $result['items'] = array();
                
                $ordertItems = $order->getItemsCollection()
                    ->setOrder('base_price', self::SORT_ORDER_DESC)
                    ->setPageSize($maxAssociated);
                
                foreach ($ordertItems as $item) {
                    $this->helper->loadCatalogData($item, $storeId, $websiteId, $multistore, $maxAssociated);
                    $result['items'][] = $this->helper->convertAttributeData($item);
                }
                
                $orderData[$order->getId()] = $result;
            }
        } catch (Exception $e) {
            return $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
        }
        
        return $this->outputJson(array(
            'orders' => $orderData,
            'stores' => $stores,
            'start' => $start
        ));
    }
}
