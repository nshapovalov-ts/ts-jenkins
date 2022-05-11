<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2019 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Controller\Sync;

use Magento\Framework\App\Action\Context;
use Eyemagine\HubSpot\Controller\AbstractSync;
use Magento\Framework\Controller\Result\JsonFactory;
use Eyemagine\HubSpot\Helper\Sync as HelperSync;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollection;
use Exception;

/**
 * Class GetSubscribers
 *
 * @package Eyemagine\HubSpot\Controller\Sync
 */
class GetSubscribers extends AbstractSync
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     *
     * @var \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    protected $subscriberCollection;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $subscriberCollection
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helperSync,
        SubscriberCollection $subscriberCollection
    ) {
        parent::__construct($context, $resultJsonFactory);
        
        $this->helper = $helperSync;
        
        $this->subscriberCollection = $subscriberCollection;
    }

    /**
     * Get newsletter subscriber data
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
            $start =  $request->getParam('start')?gmdate('Y-m-d H:i:s', $request->getParam('start')):'0';
            $end = gmdate('Y-m-d H:i:s', time() - 300);
            $lastSubscriberId = $request->getParam('id', '0');
            $maxperpage = $request->getParam('maxperpage', self::MAX_SUBSCRIBER_PERPAGE);
            $websiteId = $this->helper->getWebsiteId();
            $storeId = $this->helper->getStoreId();
            $subscriberData = array();
            $collection = $this->subscriberCollection->create();
            // setup the query and page size
            if($start){
                $collection->addFieldToFilter('change_status_at', array(
                    array(
                        'from' => $start,
                        'to' => $end,
                        'date' => true
                    )
                ));
            }
            
            $collection->addFieldToFilter('subscriber_email', array(
                'like' => '%@%'
            ))
                ->addFieldToFilter('subscriber_id', array(
                'gt' => $lastSubscriberId
                ))
                ->addFieldToFilter('subscriber_status', array('eq' => 1)) // include Newsletter subscribers with status = "Subscribed" only
                ->setOrder('change_status_at', self::SORT_ORDER_ASC)
                ->setOrder('subscriber_id', self::SORT_ORDER_ASC)
                ->setPageSize($maxperpage);
            
            // only add the filter if store id > 0
            if (! ($multistore) && $storeId) {
                $collection->addFieldToFilter('store_id', array(
                    'eq' => $storeId
                ));
            }
            
            foreach ($collection as $subscriber) {
                $subscriberData[$subscriber->getId()] = $subscriber->getData();
            }
        } catch (Exception $e) {
            return $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
        }
        
        return $this->outputJson(array(
            'subscribers' => $subscriberData,
            'website' => $websiteId,
            'store' => $storeId
        ));
    }
}
