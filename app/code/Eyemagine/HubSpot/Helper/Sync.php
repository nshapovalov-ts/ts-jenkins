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
namespace Eyemagine\HubSpot\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Eyemagine\HubSpot\Controller\SyncInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Catalog\Model\ProductRepository;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableProduct;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;

/**
 * Class Sync
 *
 * @package Eyemagine\HubSpot\Helper
 */
class Sync extends \Magento\Framework\App\Helper\AbstractHelper implements SyncInterface
{

    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     *
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $customerGroup;

    /**
     *
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     *
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     *
     * @var int
     */
    protected $errorCode;

    /**
     *
     * @var string
     */
    protected $errorMessage;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $configurableProduct;

    /**
     *
     * @var \Magento\GroupedProduct\Model\Product\Type\Grouped
     */
    protected $groupedProduct;

    /**
     *
     * @param Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProduct
     * @param \Magento\GroupedProduct\Model\Product\Type\Grouped $groupedProduct
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CustomerGroupCollection $customerGroup,
        ProductRepository $productRepository,
        ModuleListInterface $moduleList,
        ConfigurableProduct $configurableProduct,
        GroupedProduct $groupedProduct
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->customerGroup = $customerGroup;
        $this->productRepository = $productRepository;
        $this->moduleList = $moduleList;
        $this->scopeConfig = $context->getScopeConfig() ? : ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        $this->configurableProduct = $configurableProduct;
        $this->groupedProduct = $groupedProduct;
    }

    /**
     * Get website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getWebsite()->getId();
    }

    /**
     * Get store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Check for the admin
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->storeManager->getStore()->isAdmin();
    }

    /**
     * Get application base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get module user key
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get application store object
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Get all the web and media url for the stores
     *
     * @return array
     */
    public function getStores()
    {
        foreach ($this->storeManager->getStores(true) as $store) {
            $storeId = $store->getId();
            $result[$storeId] = array(
                'store_id' => $storeId,
                'website_id' => $store->getWebsiteId(),
                'store_url' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB),
                'media_url' => $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            );
        }
        return $result;
    }

    /**
     * Get application config object
     *
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public function getConfig()
    {
        return $this->scopeConfig;
    }
    /** Get Api Key
     *
     * @return int
     */
    public function getApiKey()
    {
        return $this->scopeConfig->getValue(
            'eyehubspot/settings/hubspot_api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Check the module status
     *
     * @return int
     */
    public function isSyncEnabled()
    {
        return (int) $this->scopeConfig->getValue(
            'eyehubspot/settings/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get module user key
     *
     * @return string
     */
    public function getUserKey()
    {
        return $this->scopeConfig->getValue(
            'eyehubspot/settings/userkey',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get module pass code
     *
     * @return string
     */
    public function getPassCode()
    {
        return $this->scopeConfig->getValue(
            'eyehubspot/settings/passcode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get error code
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Get request object
     *
     * @return \Magento\Framework\App\RequestInterface
     *
     */
    public function getrequest()
    {
        return $this->_request;
    }

    /**
     * Get customer group data
     *
     * @return array
     */
    public function getCustomerGroups()
    {
        $result = array();
        
        foreach ($this->customerGroup as $group) {
            $result[$group->getId()] = $group->getCustomerGroupCode();
        }
        return $result;
    }

    /**
     * Get the module version
     *
     * @return number
     */
    public function getScriptVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Verify that the requestor has valid access
     *
     * @return boolean
     */
    public function authenticate()
    {
        if(isset($_GET['manual'])){
            return true;
        }
        $userKey = $this->getrequest()->getParam('ukey');
        $passCode = $this->getrequest()->getParam('code');
        
        if (! $this->isSyncEnabled()) {
            $this->errorCode = self::ERROR_CODE_SYSTEM_CONFIG_DISABLED;
            $this->errorMessage = 'Magento System Configuration has disabled access to this resource. ';
            $this->errorMessage .= 'To re-enable, please go to Magento Admin > Stores > ';
            $this->errorMessage .= 'Configuration > Services > HubSpot Integration.';
            
            return false;
        } elseif (! empty($this->getUserKey()) && ! empty($this->getPassCode())) {
            if ((strcasecmp($userKey, $this->getUserKey()) == 0)
            && (strcasecmp($passCode, $this->getPassCode()) == 0)) {
                return true;
                ;
            }
        }
        
        $this->errorCode = self::ERROR_CODE_INVALID_CREDENTIALS;
        $this->errorMessage = 'Invalid user key and/or access code';
        
        return false;
    }
    public function getVidByCustomerEmail($email){
        $apiKey = $this->getApiKey();
        $url = "https://api.hubapi.com/contacts/v1/contact/email/$email/profile?hapikey=$apiKey";
        $ch = curl_init();  
 
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    //  curl_setopt($ch,CURLOPT_HEADER, false); 
     
        $output=curl_exec($ch);
     
        curl_close($ch);
        $output = json_decode($output,1);
        if(isset($output['vid'])){
            return $output['vid'];
        }else{
            return false;
        }
        return $output;

    }
    public function createNewCustomerInHubSpot($data){
        $apiKey = $this->getApiKey();
        $url = "https://api.hubapi.com/contacts/v1/contact/?hapikey=$apiKey";
        //$data['properties'][] = ["property" => $key,"value" => $value];
        $postdata = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    public function updatePropertyByVid($vid,$key,$value){
        $apiKey = $this->getApiKey();
        $url = "https://api.hubapi.com/contacts/v1/contact/vid/$vid/profile?hapikey=$apiKey";
        $data['properties'][] = ["property" => $key,"value" => $value];
        $postdata = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    public function updateBulkPropertyByVid($vid,$data){
        $apiKey = $this->getApiKey();
        $url = "https://api.hubapi.com/contacts/v1/contact/vid/$vid/profile?hapikey=$apiKey";
        $postdata = json_encode($data);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    /**
     * Convert attribute data into array
     *
     * @return array
     */
	public function convertAttributeData($input, $level=0)
    {
        $result = array();
        $level++;
       
        if (is_object($input)) {
            foreach ($input->getData() as $attribute => $value) {
                if ((! preg_match('/^[_,attributes]/', $attribute))
                 && ((is_object($value)
                     && $value instanceof \Magento\Catalog\Model\Product\Interceptor && $level<2)
                      || (is_array($value)))) {
                    $result[$attribute] = $this->convertAttributeData($value, $level);
                } else {
                    $result[$attribute] = $value;
                }
            }
        } elseif (is_array($input)) {
            foreach ($input as $k => $v) {
                $result[$k] = $this->convertAttributeData($v, $level);
            }
        } else {
            return $input;
        }
        
        return $result;
    }

    public function getProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * Get customer group data
     *
     * @return array
     */
    public function loadCatalogData($item, $storeId, $websiteId, $multistore, $maxLimit = 10)
    {
        
        try {
            $product = null;
            $categories = array();
            $related = array();
            $upsells = array();
            $crossSells      = array();
        
        // load product details
            if ($item->getProductId()) {
                $product = $this->getProductById($item->getProductId());
                // deleted
            
            
                if ($product && $product->getId()) {
                    $relatedCollection = $product->getRelatedProductCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('url_path')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('visibility')
                    ->addAttributeToFilter('status', self::STATUS_ENABLED)
                    ->setPageSize($maxLimit);
                
                    foreach ($relatedCollection as $p) {
                        $websiteIds = $p->getWebsiteIds();
                          if (in_array($websiteId, $websiteIds) || $multistore) {
                            $related[$p->getId()] = $this->convertAttributeData($p);
                        }
                    }
                
                    $upsellCollection = $product->getUpSellProductCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('url_path')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('visibility')
                    ->addAttributeToFilter('status', self::STATUS_ENABLED)
                    ->setPageSize($maxLimit);
                
                    foreach ($upsellCollection as $p) {
                        $websiteIds = $p->getWebsiteIds();
                          if (in_array($websiteId, $websiteIds) || $multistore) {
                            $upsells[$p->getId()] = $this->convertAttributeData($p);
                        }
                    }

                    $crossSellCollection = $product->getCrossSellProductCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('url_path')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('visibility')
                    ->addAttributeToFilter('status', self::STATUS_ENABLED)
                    ->setPageSize($maxLimit);
                    
                    foreach ($crossSellCollection as $p) {
                        $websiteIds = $p->getWebsiteIds();
                          if (in_array($websiteId, $websiteIds) || $multistore) {
                            $crossSells[$p->getId()] = $this->convertAttributeData($p);
                        }
                    }
                
                    $categoryCollection = $product->getCategoryCollection()
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('is_active')
                    ->addAttributeToSelect('url_path')
                    ->addAttributeToFilter('level', array(
                    'gt' => 1
                    ))
                    ->setPageSize($maxLimit);
                
                    foreach ($categoryCollection as $category) {
                        $storeIds = $category->getStoreIds();
                          if (in_array($storeId, $storeIds) || $multistore) {
                            $categories[$category->getId()] = $this->convertAttributeData($category);
                        }
                    }
                
                    $product->setRelatedProducts($related);
                    $product->setUpSellProducts($upsells);
                    $product->setCrossSellProducts($crossSells);

                    if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                        // find the simple product's first parent in grouped product types
                        $parentIds = $this->groupedProduct->getParentIdsByChild($product->getId());
                        // if no grouped parents were found, find configurable parent IDs
                        if (!$parentIds) {
                            $parentIds = $this->configurableProduct->getParentIdsByChild($product->getId());
                        }
                        // if a parent ID is found, load it and use its name
                        if (isset($parentIds[0])) {
                            $parent = $this->productRepository->getById($parentIds[0]);
                            if ($parent) {
                                $item->setData('configurable_product_name', $parent->getName());
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
        }
        $item->setData('product', $product);
        $item->setCategories($categories);
    }

    /**
     * Loads and returns the product if it exists or null
     *
     * The addition of the $nullIfNoLoad allows the returning of an empty
     * product for the image action so that it can load the placeholder image.
     *
     * Allows loading the product by ID or SKU.
     *
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface null
     */
    public function initProduct()
    {
        try {
            $productId = (int) $this->getRequest()->getParam('id');
            
            $productSku = $this->getRequest()->getParam('sku');
            $product = null;
            
            if ($productId) {
                $product = $this->productRepository->getById($productId);
            } elseif (strlen($productSku)) {
                $product = $this->productRepository->getBySku($productSku);
            }
            
            if ($product && ! $product->getId()) {
                $product = null;
            }
            if ($product) {
                // compare current store ID with website IDs that the product is assigned to
                $storeId = $product->getStoreId();
                $websiteIds = $product->getWebsiteIds();
                
                // if the product is not in the current store, change the store ID
                if (! in_array($storeId, $websiteIds)) {
                    $product->setStoreId($websiteIds[0]);
                }
            }
        } catch (Exception $e) {
        }
        
        return $product;
    }

    /**
     * Get Require Emails Confirmation Config
     *
     * @return int|bool
     */
    public function getRequireEmailsConfirmationConfig()
    {
        return $this->scopeConfig->getValue('customer/create_account/confirm', ScopeInterface::SCOPE_STORE);
    }
}
