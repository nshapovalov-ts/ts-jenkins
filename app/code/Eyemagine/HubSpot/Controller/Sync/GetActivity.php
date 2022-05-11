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
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Eyemagine\HubSpot\Helper\Sync as HelperSync;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObjectFactory;
use Magento\Reports\Model\ResourceModel\Event\CollectionFactory as EventCollection;
use Exception;

/**
 * Class GetActivity
 *
 * @package Eyemagine\HubSpot\Controller\Sync
 */
class GetActivity extends AbstractSync
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     *
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObject;

    /**
     *
     * @var \Magento\Reports\Model\ResourceModel\Event\Collection
     */
    protected $eventCollection;

    /**
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollection;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Reports\Model\ResourceModel\Event\Collection $eventCollection
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\DataObjectFactory $dataObject
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helperSync,
        ProductCollection $productCollection,
        EventCollection $eventCollection,
        ResourceConnection $resource,
        DataObjectFactory $dataObject
    ) {
        parent::__construct($context, $resultJsonFactory);
        
        $this->helper = $helperSync;
        $this->productCollection = $productCollection;
        $this->resource = $resource;
        $this->dataObject = $dataObject;
        $this->eventCollection = $eventCollection;
    }

    /**
     * Get customer activity data
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
            $maxperpage = $request->getParam('maxperpage', self::MAX_CUSTOMER_PERPAGE);
            $maxAssociated = $request->getParam('maxassoc', self::MAX_ASSOC_PRODUCT_LIMIT);
            $start = gmdate('Y-m-d H:i:s', $request->getParam('start', 0));
            $end = gmdate('Y-m-d H:i:s', time() - 300); // 300
            $websiteId = $this->helper->getWebsiteId();
            $storeId = $this->helper->getStoreId();
            
            $read = $this->resource->getConnection('core_read');
            $customerData = array();
            
            try {
                // because of limitations in the log areas of magento, we cannot use the
                // standard collection to retreive the results
                $select = $read->select()
                    ->from(array(
                    'lc' => $this->resource->getTableName('customer_log')
                    ))
                    ->joinInner(array(
                    'lv' => $this->resource->getTableName('customer_visitor')
                    ), 'lc.customer_id = lv.customer_id')
                    ->
                joinInner(array(
                    'c' => $this->resource->getTableName('customer_entity')
                ), 'c.entity_id = lc.customer_id', array(
                    'email' => 'email',
                    'customer_since' => 'created_at'
                ))
                    ->
                where('lc.customer_id > 0');
                // only add the filter if website id > 0
                if (! ($multistore) && $websiteId) {
                    $select->where("c.website_id = '$websiteId'");
                }
                $select->where("lv.last_visit_at >= '$start'")
                    ->where("lv.last_visit_at < '$end'")
                    ->order('lv.last_visit_at')
                    ->limit($maxperpage);
                
                $collection = $read->fetchAll($select);
            } catch (Exception $e) {
                $this->outputError(self::ERROR_CODE_UNSUPPORTED_SQL, 'DB Exception on query', $e);
                return;
            }
            
            foreach ($collection as $assoc) {
                $item['data'] = $assoc;
                $log = $this->dataObject->create($item);
                
                $customerId = $log->getCustomerId();
                
                // merge and replace older data with newer
                if (isset($customerData[$customerId])) {
                    $temp = $customerData[$customerId];
                    $log->addData($temp->getData());
                    $log->setFirstVisitAt($temp->getFirstVisitAt());
                } else {
                    $log->setViewed($this->getProductViewedList($customerId, $storeId, $multistore, $maxAssociated));
                    $log->setCompare($this->getProductCompareList($customerId, $storeId, $multistore, $maxAssociated));
                    $log->setWishlist($this->getProductWishlist($customerId, $storeId, $multistore, $maxAssociated));
                }
                
                $log->unsetData('session_id');
                $customerData[$customerId] = $log;
            }
        } catch (Exception $e) {
            $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
            return;
        }
        
        return $this->outputJson(array(
            'visitors' => $this->helper->convertAttributeData($customerData),
            'website' => $websiteId,
            'store' => $storeId
        ));
    }

    /**
     * Load the customer recently viewed products list
     *
     * @param int $customerId
     * @return array
     */
    public function getProductViewedList($customerId, $storeId = 0, $multistore = 0, $limit = 10)
    {
        $customerId = (int) $customerId;
        
        $maxpagesize = ((int) $limit) ? (int) $limit : 10;
        $returnData = array();
        
        if ($customerId) {
            try {
                $collection = $this->eventCollection->create()
                    ->addRecentlyFiler(self::EVENT_PRODUCT_VIEW, $customerId, 0)
                    ->setPageSize($maxpagesize)
                    ->setOrder('logged_at', self::SORT_ORDER_DESC);
                
                if (! ($multistore) && $storeId) {
                    $collection->addStoreFilter(array(
                        $storeId
                    ));
                }
                
                $productIds = array();
                
                foreach ($collection as $event) {
                    $productIds[] = $event->getObjectId();
                }
                
                if (count($productIds)) {
                    $productCollection = $this->productCollection->create()
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('sku')
                        ->addAttributeToSelect('price')
                        ->addAttributeToSelect('image')
                        ->addAttributeToSelect('url_path')
                        ->addIdFilter($productIds);
                    
                    if (! ($multistore) && $storeId) {
                        $productCollection->setStoreId($storeId)->addStoreFilter($storeId);
                    }
                    
                    foreach ($productCollection as $viewed) {
                        $returnData[] = $this->helper->convertAttributeData($viewed);
                    }
                }
            } catch (Exception $e) {
                $returnData['error'] = self::ERROR_CODE_UNSUPPORTED_FEATURE;
            }
        }
        
        return $returnData;
    }

    /**
     * Load the customer compare list
     *
     * @param int $customerId
     * @return array
     */
    public function getProductCompareList($customerId, $storeId = 0, $multistore = 0, $limit = 10)
    {
        $customerId = (int) $customerId;
        
        $maxpagesize = ((int) $limit) ? (int) $limit : 10;
        $returnData = array();
        
        if ($customerId) {
            try {
                $collection = $this->eventCollection->create()
                    ->addRecentlyFiler(self::EVENT_PRODUCT_COMPARE, $customerId, 0)
                    ->setPageSize($maxpagesize)
                    ->setOrder('logged_at', self::SORT_ORDER_DESC);
                
                if (! ($multistore) && $storeId) {
                    $collection->addStoreFilter(array(
                        $storeId
                    ));
                }
                
                $productIds = array();
                
                foreach ($collection as $event) {
                    $productIds[] = $event->getObjectId();
                }
                
                if (count($productIds)) {
                    $productCollection = $this->productCollection->create()
                        ->addAttributeToSelect('name')
                        ->addAttributeToSelect('sku')
                        ->addAttributeToSelect('price')
                        ->addAttributeToSelect('image')
                        ->addAttributeToSelect('url_path')
                        ->addAttributeToSelect('status')
                        ->addIdFilter($productIds);
                    
                    if (! ($multistore) && $storeId) {
                        $productCollection->setStoreId($storeId)->addStoreFilter($storeId);
                    }
                    
                    foreach ($productCollection as $compare) {
                        $returnData[] = $this->helper->convertAttributeData($compare);
                    }
                }
            } catch (Exception $e) {
                $returnData['error'] = self::ERROR_CODE_UNSUPPORTED_FEATURE;
            }
        }
        
        return $returnData;
    }

    /**
     * Load the customer wishlist
     *
     * @param int $customerId
     * @return array
     */
    public function getProductWishlist($customerId, $storeId = 0, $multistore = 0, $limit = 10)
    {
        $customerId = (int) $customerId;
        
        $maxpagesize = ((int) $limit) ? (int) $limit : 10;
        $returnData = array();
        
        if ($customerId) {
            try {
                $collection = $this->eventCollection->create()
                    ->addRecentlyFiler(self::EVENT_PRODUCT_TO_WISHLIST, $customerId, 0)
                    ->setPageSize($maxpagesize)
                    ->setOrder('logged_at', self::SORT_ORDER_DESC);
                
                $productIds = array();
                
                foreach ($collection as $event) {
                    $productIds[] = $event->getObjectId();
                }
                
                if (count($productIds)) {
                    $wishListItemCollection = $this->productCollection->create()
                        ->addAttributeToSelect('name')
                        ->addIdFilter($productIds);
                    
                    if (! ($multistore) && $storeId) {
                        $wishListItemCollection->setStoreId($storeId)->addStoreFilter($storeId);
                    }
                    foreach ($wishListItemCollection as $item) {
                        $returnData[]['name'] = $item->getName(); // Get Product Name
                    }
                }
            } catch (Exception $e) {
                $returnData['error'] = self::ERROR_CODE_UNSUPPORTED_FEATURE;
            }
        }
        
        return $returnData;
    }
}
