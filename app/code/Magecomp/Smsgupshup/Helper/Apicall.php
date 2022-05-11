<?php 
namespace Magecomp\Smsgupshup\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_GUPSHUP_USERNAME = 'smspro/smsgatways/gupshupusername';
    const XML_GUPSHUP_PASSWORD = 'smspro/smsgatways/gupshuppassword';
	const XML_GUPSHUP_API_URL = 'smspro/smsgatways/gupshupapiurl';

	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("GupShup");
    }

    public function getUsername(){
        return $this->scopeConfig->getValue(
            self::XML_GUPSHUP_USERNAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPassword()	{
        return $this->scopeConfig->getValue(
            self::XML_GUPSHUP_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_GUPSHUP_API_URL,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}

	public function validateSmsConfig() {
        return $this->getApiUrl() && $this->getPassword() && $this->getUsername();
    }

	public function callApiUrl($mobilenumbers,$message)
	{
        try
        {
            $user = $this->getUsername();
            $password = $this->getPassword();
            $ch = curl_init();
            if (!$ch)
            {
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt($ch, CURLOPT_URL,$this->getApiUrl());
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,		"msg=$message&v=1.1&userid=$user&password=$password&send_to=$mobilenumbers&msg_type=text&method=sendMessage");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $curlresponse = curl_exec($ch); // execute

            if (curl_error($ch)) {
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