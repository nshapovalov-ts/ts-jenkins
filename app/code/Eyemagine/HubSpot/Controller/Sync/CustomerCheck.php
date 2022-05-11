<?php

/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2020 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Controller\Sync;

use Magento\Framework\App\Action\Context;
use Eyemagine\HubSpot\Controller\AbstractSync;
use Magento\Framework\Controller\Result\JsonFactory;
use Eyemagine\HubSpot\Helper\Sync as HelperSync;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollection;
use Magento\Customer\Model\CustomerFactory ;
use Exception;

/**
 * Class GetCustomers
 *
 * @package Eyemagine\HubSpot\Controller\Sync
 */
class CustomerCheck extends AbstractSync
{

    /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    /**
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollection;
    
     /**
     *
     * @var array
     */
    protected $excludeCustomerData;
    protected $customerFactory;
    

    /**
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Eyemagine\HubSpot\Helper\Sync $helperSync
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollection
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HelperSync $helper,
         CustomerFactory $customerFactory,
        CustomerCollection $customerCollection
    ) {
        parent::__construct($context, $resultJsonFactory);
        
        $this->helper = $helper;
        
        $this->customerCollection = $customerCollection;
        $this->customerFactory = $customerFactory;
        
        $this->excludeCustomerData = array(
            'password_hash',
            'rp_token',
            'rp_token_created_at'
        );
    }

    /**
     * Get customer data
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
            $maxperpage = $request->getParam('maxperpage', self::MAX_CUSTOMER_PERPAGE);
            $websiteId = $this->helper->getWebsiteId();
            $storeId = $this->helper->getStoreId();
            $customerData = array();
            
            $custGroups = $this->helper->getCustomerGroups();
            
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
            $regionFactory = $objectManager->get('Magento\Directory\Model\RegionFactory');
            $_customer = $customerRepository->getById($entityId);
            /*get customer custom attribute value by customer attribute code */
            //$cattrValue = $customer->getCustomAttribute('custom_attribute')->getValue();
            $customer = $this->customerFactory->create()->load($entityId);
            $this->_eventManager->dispatch(
                'customer_register_success_after_logout',
                ['account_controller' => $this, 'customer' => $customer]
            );
            die;
            if ($customer->getDefaultBilling()) {
                $customer->setDefaultBillingAddress(
                    $this->helper->convertAttributeData(
                        $customer->getDefaultBillingAddress()
                    )
                );
            }
                
            // clear unwanted data
            foreach ($this->excludeCustomerData as $exclude) {
                $customer->unsetData($exclude);
            }
            
            $groupId = (int) $customer->getGroupId();
            if (isset($custGroups[$groupId])) {
                $customer->setCustomerGroup($custGroups[$groupId]);
            }
            $allCustomerAttributes = array_keys($_customer->getCustomAttributes());
            foreach ($allCustomerAttributes as $attributeCode) {
                $attributeValueId = $customer->getData($attributeCode);
                $attribute = $customer->getResource()->getAttribute($attributeCode);
                if($attribute->usesSource() && $attributeValueId && $attribute->getSource()->toOptionArray()){
                    $optionText =  $attribute->getSource()->getOptionText($attributeValueId);
                    if(strpos($attribute->getData('frontend_input'),'multiselect') !== false && !$optionText) {
                        $optionText = [];
                        $attributeValues = explode(",",$attributeValueId);
                        foreach($attribute->getSource()->toOptionArray() as $attributeOptions){
                            if(in_array($attributeOptions['value'], $attributeValues)){
                                $optionText[] =  $attributeOptions['label'];
                            }
                        }
                    }
                    if(is_array($optionText)){
                        $optionText = implode(",", $optionText);
                    }
                    $customer->setData($attributeCode,$optionText);
                }
            }
            foreach ($customer->getData() as $key => $value) {
                if($key == 'entity_id'){
                    $customer->setData('eyemagine_customer_id',$value);
                    $customer->unsetData($key);
                    
                }else if($key == 'is_approved'){
                    $customer->setData('eyemagine_is_approved',$value);
                    $customer->unsetData($key);
                }else if($key == 'email'){
                    $customer->setData('eyemagine_activity_email',$value);
                }
                else if(in_array($key,['firstname','lastname','middlename'])){
                    
                }
                else if(!(is_array( $value) || is_object( $value)) ){
                    $customer->setData('eyemagine_'.$key,$value);
                    $customer->unsetData($key);
                }
            }
            $includeList = [
                'postcode' => 'eyemagine_extra_postcode',
                'street' => 'address',
                'country_id' => 'country',
                'region_id' => 'state',
            ];
            foreach ($customer->getData('default_billing_address') as $key => $value) {
                if(isset($includeList[$key]) ){
                    if($key == 'region_id'){
                        $region = $regionFactory->create()->load($value);
                        $value = $region->getData('name'); 
                    }
                    $customer->setData($includeList[$key],$value);
                }
            }
            $customer->unsetData('default_billing_address');
            $customerDetail = $customer->getData();
            $customerWithProperties = [];
            foreach($customerDetail as $property => $value){
                $customerWithProperties['properties'][] = ["property" =>  $property, "value" =>  $property];
            }
            return $this->outputJson($customerDetail);
        } catch (Exception $e) {
            return $this->outputError(self::ERROR_CODE_UNKNOWN_EXCEPTION, 'Unknown exception on request', $e);
        }
    }
}
