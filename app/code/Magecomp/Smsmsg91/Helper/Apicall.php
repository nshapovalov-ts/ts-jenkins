<?php
namespace Magecomp\Smsmsg91\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_MSG91_API_SENDERID = 'smspro/smsgatways/msg91senderid';
    const XML_MSG91_API_AUTHKEY = 'smspro/smsgatways/msg91authkey';
	const XML_MSG91_API_URL = 'smspro/smsgatways/msg91apiurl';
    const XML_MSG91_API_ROUTER = 'smspro/smsgatways/msg91route';


	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("Msg91");
    }

    public function getApiSenderId(){
        return $this->scopeConfig->getValue(
            self::XML_MSG91_API_SENDERID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAuthKey()	{
        return $this->scopeConfig->getValue(
            self::XML_MSG91_API_AUTHKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getRouter()	{
        return $this->scopeConfig->getValue(
            self::XML_MSG91_API_ROUTER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_MSG91_API_URL,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}

	public function validateSmsConfig() {
        return $this->getApiUrl() && $this->getAuthKey() && $this->getApiSenderId();
    }

	public function callApiUrl($mobilenumbers,$message,$dltid)
	{
        try
        {
            $url = $this->getApiUrl();
            $authkey = $this->getAuthKey();
            $senderid = $this->getApiSenderId();
            $router = $this->getRouter();

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
            "authkey=$authkey&mobiles=$mobilenumbers&message=$message&sender=$senderid&route=$router&country=0&DLT_TE_ID=$dltid");
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
