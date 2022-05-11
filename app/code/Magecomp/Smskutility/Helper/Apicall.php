<?php 
namespace Magecomp\Smskutility\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_KUTILITY_API_SENDERID = 'smspro/smsgatways/kutilitysenderid';
    const XML_KUTILITY_API_AUTHKEY = 'smspro/smsgatways/kutilityauthkey';
	const XML_KUTILITY_API_URL = 'smspro/smsgatways/kutilityapiurl';
    const XML_KUTILITY_ROUTER_ID = 'smspro/smsgatways/kutilityrouteid';

	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("Kutility");
    }

    public function getApiSenderId(){
        return $this->scopeConfig->getValue(
            self::XML_KUTILITY_API_SENDERID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAuthKey()	{
        return $this->scopeConfig->getValue(
            self::XML_KUTILITY_API_AUTHKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getRouterId()	{
        return $this->scopeConfig->getValue(
            self::XML_KUTILITY_ROUTER_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_KUTILITY_API_URL,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}

	public function validateSmsConfig() {
        return $this->getApiUrl() && $this->getAuthKey() && $this->getApiSenderId();
    }
	
	public function callApiUrl($mobilenumbers,$message)
	{


        try
        {
            $url = $this->getApiUrl();
            $authkey = $this->getAuthKey();
            $senderid = $this->getApiSenderId();
            $routerid = $this->getRouterId();


            $ch = curl_init();
            if (!$ch)
            {
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,
            "key=$authkey&routeid=$routerid&type=text&contacts=$mobilenumbers&senderid=$senderid&msg=$message");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $curlresponse = curl_exec($ch); // execute


            if(curl_errno($ch))
            {
                curl_close($ch);
                return 'Error: '.curl_error($ch);
            }
            curl_close($ch);
            return true;
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
	}
}