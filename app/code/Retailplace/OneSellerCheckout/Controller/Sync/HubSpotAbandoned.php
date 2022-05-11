<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Controller\Sync;

use Exception;
use Eyemagine\HubSpot\Controller\Sync\GetAbandoned;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;

/**
 * Class HubSpotAbandoned
 */
class HubSpotAbandoned extends GetAbandoned
{
    /**
     * Get abandoned cart data
     *
     * @return \Magento\Framework\Controller\Result\Json
     * @see \Eyemagine\HubSpot\Controller\Sync\GetAbandoned::execute()
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
            $start = gmdate('Y-m-d H:i:s', (int) $request->getParam('start', 0));
            $end = gmdate('Y-m-d H:i:s', time() - (int) $request->getParam('offset', self::IS_ABANDONED_IN_SECS));
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
                ->addFieldToFilter(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID, array(
                    'null' => true
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
