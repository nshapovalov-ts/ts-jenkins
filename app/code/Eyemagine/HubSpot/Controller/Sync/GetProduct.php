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
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Exception;

/**
 * Class GetProduct
 *
 * @package Eyemagine\HubSpot\Controller
 */
class GetProduct extends AbstractSync
{
    const OBJECT_TYPE_PRODUCT = 'PRODUCT';
    const PRICE_SOURCE = 'API';

    /**
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Model\Product|ProductFactory $productFactory
     */
    protected $productFactory;

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
        ProductFactory $productFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context, $resultJsonFactory);

        $this->helper = $helperSync;
        $this->productFactory = $productFactory;
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

            if (!$request->getParam('product_id', null)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Product ID does not exist.')
                );
            }

            $productId = $request->getParam('product_id');
            $product = $this->productFactory->create()->load($productId);

            $hubspot_portal_id = $this->scopeConfig->getValue('eyehubspot/settings/hubspot_portal_id', ScopeInterface::SCOPE_STORE);
            $timestamp = strtotime($product->getUpdatedAt()) . '000'; // Unix timestamp in milliseconds
            $price = number_format($product->getPrice(), 2);
            $name = $product->getName();
            $description = strip_tags($product->getDescription());

            $productData = array(
                'objectType' => self::OBJECT_TYPE_PRODUCT,
                'portalId' => "{$hubspot_portal_id}",
                'objectId' => $productId,
                'properties' => array(
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
                    'description' => array(
                        'versions' => array(
                            array(
                                'name' => 'description',
                                'value' => $description,
                                'timestamp' => $timestamp,
                                'source' => self::PRICE_SOURCE,
                                'sourceVid' => array(),
                            )
                        ),
                        'value' => $description,
                        'timestamp' => $timestamp,
                        'source' => self::PRICE_SOURCE,
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
