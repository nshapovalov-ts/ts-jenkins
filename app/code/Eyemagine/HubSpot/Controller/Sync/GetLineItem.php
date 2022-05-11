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
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Exception;

/**
 * Class Get Line Item
 *
 * @package Eyemagine\HubSpot\Controller
 */
class GetLineItem extends AbstractSync
{
    const OBJECT_TYPE_LINE_ITEM = 'LINE_ITEM';
    const PRICE_SOURCE = 'API';

    /**
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     */
    protected $quoteItemFactory;

    /**
     * @var Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Catalog\Model\Product $productFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helperSync,
        ItemFactory $quoteItemFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context, $resultJsonFactory);

        $this->helper = $helperSync;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get product data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            if (!$this->helper->authenticate()) {
                return $this->outputError($this->helper->getErrorCode(), $this->helper->getErrorMessage(), null);
            }
            $request = $this->getRequest();

            if (!$request->getParam('quote_item_id', null)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Quote Item ID does not exist.')
                );
            }

            $quoteItemId = $request->getParam('quote_item_id');
            $quoteItem = $this->quoteItemFactory->create()->load($quoteItemId);

            $hubspot_portal_id = $this->scopeConfig->getValue('eyehubspot/settings/hubspot_portal_id', ScopeInterface::SCOPE_STORE);
            $timestamp = strtotime($quoteItem->getCreatedAt()) . '000'; // Unix timestamp in milliseconds
            $price = number_format($quoteItem->getPrice(), 2);
            $quantity = number_format($quoteItem->getQty(), 0);
            $name = $quoteItem->getName();

            $productData = array(
                'objectType' => self::OBJECT_TYPE_LINE_ITEM,
                'portalId' => "{$hubspot_portal_id}",
                'objectId' => $quoteItemId,
                'properties' => array(
                    'quantity' => array(
                        'versions' => array(
                            array(
                                'name' => 'quantity',
                                'value' => $quantity,
                                'timestamp' => $timestamp,
                                'source' => self::PRICE_SOURCE,
                                'sourceVid' => array(),
                            )
                        ),
                        'value' => $quantity,
                        'timestamp' => $timestamp,
                        'source' => self::PRICE_SOURCE,
                        'sourceId' => null,
                    ),
                    'price' => array(
                        'versions' => array(
                            array(
                                'name' => 'price',
                                'value' => $price,
                                'timestamp' => $timestamp,
                                'source' => self::PRICE_SOURCE,
                                'sourceVid' => array(),
                            )
                        ),
                        'value' => $price,
                        'timestamp' => $timestamp,
                        'source' => self::PRICE_SOURCE,
                        'sourceId' => null,
                    ),
                    'name' => array(
                        'versions' => array(
                            array(
                                'name' => 'name',
                                'value' => $name,
                                'timestamp' => $timestamp,
                                'source' => self::PRICE_SOURCE,
                                'sourceVid' => array(),
                            )
                        ),
                        'value' => $name,
                        'timestamp' => $timestamp,
                        'source' => self::PRICE_SOURCE,
                        'sourceId' => null,
                    ),
                    'hs_product_id' => array(
                        'versions' => array(
                            array(
                                'name' => 'hs_product_id',
                                'value' => '',
                                'sourceVid' => array(),
                            )
                        ),
                        'value' => '',
                        'timestamp' => 0,
                        'source' => null,
                        'sourceId' => null,
                    ),
                ),
                'version' => 1,
                'isDeleted' => false,
            );

        } catch (Exception $e) {
            return $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
        }

        return $this->outputJson($productData);
    }
}
