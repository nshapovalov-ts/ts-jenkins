<?php

namespace Eyemagine\HubSpot\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerFactory;
use Eyemagine\HubSpot\Helper\Sync as HelperSync;

class SendCustomerToHubspot implements ObserverInterface
{

    protected $_customerRepositoryInterface;
    protected $customerFactory;
    protected $regionFactory;
    protected $eyeImagineSyncHelper;
    /**
     *
     * @var array
     */
    protected $excludeCustomerData;
     /**
     *
     * @var \Eyemagine\HubSpot\Helper\Sync
     */
    protected $helper;

    public function __construct(
        HelperSync $helper,
        \Eyemagine\HubSpot\Helper\Sync $eyeImagineSyncHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        CustomerFactory $customerFactory
    ) {
        $this->helper = $helper;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerFactory = $customerFactory;
        $this->regionFactory = $regionFactory;
        $this->eyeImagineSyncHelper = $eyeImagineSyncHelper;
        $this->excludeCustomerData = array(
            'password_hash',
            'rp_token',
            'rp_token_created_at',
            'updated_at',
            'default_billing',
            'created_at',
            'increment_id',
            'default_shipping',
            'middlename',
            'disable_auto_group_change',
            'confirmation',
        );
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return $this;
        $customer = $observer->getEvent()->getCustomer();
        $customerId = $customer->getId();
        $customerEmail = $customer->getEmail();
        
        $_customer = $this->_customerRepositoryInterface->getById($customerId);
        $customer = $this->customerFactory->create()->load($customerId);
        if ($customer->getDefaultBilling()) {
            $customer->setDefaultBillingAddress(
                $this->helper->convertAttributeData(
                    $customer->getDefaultBillingAddress()
                )
            );
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
        // clear unwanted data
        foreach ($this->excludeCustomerData as $exclude) {
            $customer->unsetData($exclude);
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
                //$customer->unsetData($key);
            }
            else if($key == 'Mobile' || $key == 'mobile'){
                $customer->setData('mobilephone',$value);
                $customer->unsetData($key);
            }
            else if(in_array($key,['firstname','lastname','middlename'])){
            }
            else if($key == 'Industry' || $key == 'industry'){
                $customer->setData('industries',str_replace(",", ";", $value));
                $customer->unsetData($key);
            }
            else if($key == 'Department' || $key == 'department'){
                $customer->setData('department',str_replace(",", ";", $value));
            }
            else if(!(is_array( $value) || is_object( $value)) ){
                $customer->setData('eyemagine_'.$key,$value);
                $customer->unsetData($key);
            }
        }


        //billing_city
        //shipping_city
        //billing_country
        //shipping_country
        $includeList = [
            'region_id' => 'eyemagine_business_state',
            'street' => 'eyemagine_business_street',
            'city' => 'shipping_city',
            'telephone' => 'eyemagine_phone_number',
            'postcode'=> 'eyemagine_business_postcode',
            'country_id' => 'shipping_country'
        ];
        foreach ($customer->getData('default_billing_address') as $key => $value) {
            if(isset($includeList[$key]) ){
                if($key == 'region_id'){
                    $region = $this->regionFactory->create()->load($value);
                    $value = $region->getData('name'); 
                }
                $customer->setData($includeList[$key],$value);
            }
        }
        $customer->unsetData('default_billing_address');
        $customerDetail = $customer->getData();
        $customerDetail['selling_platform'] =  $customerDetail['eyemagine_sell_goods_medium'] ?? "";
        $customerDetail['billing_city'] =  $customerDetail['eyemagine_suburb'] ?? "";
        $customerDetail['billing_country'] =  $customerDetail['shipping_country'] ?? "";

        //$customerDetail['industry_multiple_'] =  $customerDetail['eyemagine_industry_non_retailer'] ?? "";
        unset($customerDetail['eyemagine_sell_goods_medium']);
        unset($customerDetail['eyemagine_industry_non_retailer']);
        unset($customerDetail['eyemagine_industry_business_use']);
        unset($customerDetail['eyemagine_industry_corporate_gifting']);
        unset($customerDetail['eyemagine_retailer_type']);
        unset($customerDetail['eyemagine_suburb']);
        
        //print_r($customerDetail);die;
        $customerWithProperties = [];
        foreach($customerDetail as $property => $value){
            $customerWithProperties['properties'][] = ["property" =>  strtolower($property) , "value" =>  $value];
        }
        $vid = $this->eyeImagineSyncHelper->getVidByCustomerEmail($customerEmail);
        if($vid){
            $result = $this->eyeImagineSyncHelper->updateBulkPropertyByVid($vid,$customerWithProperties);
        }else{
            $result = $this->eyeImagineSyncHelper->createNewCustomerInHubSpot($customerWithProperties);
        }

        if(isset($_GET['manual'])){
            echo "<pre>";
            print_r($customerWithProperties);
            print_r(json_decode($result,true) );
            die;
        }
        
        return $this;
    }
}