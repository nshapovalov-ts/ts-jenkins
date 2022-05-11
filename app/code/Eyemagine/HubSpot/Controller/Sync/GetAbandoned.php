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
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollection;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollection;
use Exception;

/**
 * Class GetAbandoned
 *
 * @package Eyemagine\HubSpot\Controller\Sync
 */
class GetAbandoned extends AbstractSync
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     *
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollection;

    /**
     *
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     */
    protected $quoteItemCollection;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollection
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\Collection $quoteItemCollection
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helperSync,
        QuoteCollection $quoteCollection,
        QuoteItemCollection $quoteItemCollection
    ) {
        parent::__construct($context, $resultJsonFactory);
        
        $this->helper = $helperSync;
        $this->quoteCollection = $quoteCollection;
        $this->quoteItemCollection = $quoteItemCollection;
    }

    /**
     * Get abandoned cart data
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
            $maxperpage = $request->getParam('maxperpage', self::MAX_ORDER_PERPAGE);
            $maxAssociated = $request->getParam('maxassoc', self::MAX_ASSOC_PRODUCT_LIMIT);
            $start = gmdate('Y-m-d H:i:s', $request->getParam('start', 0));
            $end = gmdate('Y-m-d H:i:s', time() - $request->getParam('offset', self::IS_ABANDONED_IN_SECS));
            $websiteId = $this->helper->getWebsiteId();
            $storeId = $this->helper->getStoreId();
            $custGroups = $this->helper->getCustomerGroups();
            $returnData = array();
            $storeCode = $this->helper->getStoreCode();
            $stores=$this->helper->getStores();
            
            $quoteCollection = $this->quoteCollection->create()
                ->addFieldToFilter('updated_at', array(
                'from' => $start,
                'to' => $end,
                'date' => true
                ))
                ->addFieldToFilter('is_active', array(
                'neq' => 0
                ))
                ->addFieldToFilter('customer_email', array(
                'like' => '%@%'
                ))
                ->addFieldToFilter('items_count', array(
                'gt' => 0
                ))
                ->setOrder('updated_at', self::SORT_ORDER_ASC)
                ->setPageSize($maxperpage);
               
            
            // only add the filter if store id > 0
            if (! ($multistore) && $storeId) {
                $quoteCollection->addFieldToFilter('store_id', array(
                    'eq' => $storeId
                ));
            }
            
            foreach ($quoteCollection as $cart) {
                $result = $this->helper->convertAttributeData($cart);
                $groupId = (int) $cart->getCustomerGroupId();
                
                if (isset($custGroups[$groupId])) {
                    $result['customer_group'] = $custGroups[$groupId];
                } else {
                    $result['customer_group'] = 'Guest';
                }
                
                $result['website_id']       = (isset($stores[$result['store_id']]['website_id']))?  $stores[$result['store_id']]['website_id']: $websiteId;
                $result['store_url']        = (isset($stores[$result['store_id']]['store_url']))?  $stores[$result['store_id']]['store_url']: $this->helper->getBaseUrl();
                $result['media_url']        = (isset($stores[$result['store_id']]['media_url']))?  $stores[$result['store_id']]['media_url']:$this->helper->getMediaUrl();
                $result['shipping_address'] = $this->helper->convertAttributeData($cart->getShippingAddress());
                $result['billing_address'] = $this->helper->convertAttributeData($cart->getBillingAddress());
                $result['items'] = array();
                
                $cartItems = $this->quoteItemCollection->create()->setQuote($cart)
                    ->setOrder('base_price', self::SORT_ORDER_DESC)
                    ->setPageSize($maxAssociated);
                
                foreach ($cartItems as $item) {
                    if (! $item->isDeleted() && ! $item->getParentItemId()) {
                        $this->helper->loadCatalogData($item, $storeId, $websiteId, $multistore, $maxAssociated);
                        $result['items'][] = $this->helper->convertAttributeData($item);
                    }
                }
                
                // make sure there are items before adding to return
                if (count($result['items'])) {
                    $returnData[$cart->getId()] = $result;
                }
            }
        } catch (Exception $e) {
            return $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
        }
        
        return $this->outputJson(array(
            'abandoned' => $returnData,
            'stores' => $stores
        ));
    }
}
