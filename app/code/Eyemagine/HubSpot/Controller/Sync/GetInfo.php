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
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\ProductMetadata;
use Exception;

/**
 * Class Getinfo
 *
 * @package Eyemagine\HubSpot\Controller
 */
class GetInfo extends AbstractSync
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     *
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;

    /**
     *
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helperSync,
        RegionFactory $regionFactory,
        ProductMetadata $productMetadata
    ) {
        parent::__construct($context, $resultJsonFactory);
        
        $this->helper = $helperSync;
        $this->regionFactory = $regionFactory;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Get stoe info data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            if (! $this->helper->authenticate()) {
                return $this->outputError($this->helper->getErrorCode(), $this->helper->getErrorMessage(), null);
            }
            
            $infoData = array();
            
            $infoData = array(
                'store_id' => $this->helper->getStoreId(),
                'store_code' => $this->helper->getStoreCode(),
                'website_id' => $this->helper->getWebsiteId(),
                'magento_state' => $this->regionFactory->create()
                    ->load((int) $this->helper->getConfig()
                    ->getValue('shipping/origin/region_id', self::SCOPE_STORE))
                    ->getName(),
                'store_name' => $this->productMetadata->getName(),
                'business_name' => $this->helper->getConfig()->getValue(
                    'general/store_information/name',
                    self::SCOPE_STORE
                ),
                'magento_email' => $this->helper->getConfig()->getValue(
                    'trans_email/ident_general/email',
                    self::SCOPE_STORE
                ),
                'locale_code' => $this->helper->getConfig()->getValue(
                    'general/locale/code',
                    self::SCOPE_STORE
                ),
                'locale_timezone' => $this->helper->getConfig()->getValue(
                    'general/locale/timezone',
                    self::SCOPE_STORE
                ),
                'magento_country' => $this->helper->getConfig()->getValue(
                    'general/store_information/country_id',
                    self::SCOPE_STORE
                ),
                'base_url' => $this->helper->getStore()->getBaseUrl(),
                'secure_base_url' => $this->helper->getConfig()->getValue(
                    'web/secure/base_url',
                    self::SCOPE_STORE
                ),
                'media_url' => $this->helper->getMediaUrl()
            );
        } catch (Exception $e) {
            return $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
        }
        
        return $this->outputJson(array(
            'info' => $infoData,
            'script_version' => $this->helper->getScriptVersion(),
            'magento_version' =>  $this->productMetadata->getVersion(),
            'magento_edition' => $this->productMetadata->getEdition()
        ));
    }
}
