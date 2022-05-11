<?php 
namespace Magecomp\Smsmsgclub\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_MSGCLUB_API_SENDERID = 'smspro/smsgatways/msgclubsenderid';
    const XML_MSGCLUB_API_AUTHKEY = 'smspro/smsgatways/msgclubauthkey';
	const XML_MSGCLUB_API_URL = 'smspro/smsgatways/msgclubapiurl';
    const XML_MSGCLUB_CONTENTTYPE = 'smspro/smsgatways/msgclubcontenttype';

	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("MsgClub");
    }

    public function getApiSenderId(){
        return $this->scopeConfig->getValue(
            self::XML_MSGCLUB_API_SENDERID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAuthKey()	{
        return $this->scopeConfig->getValue(
            self::XML_MSGCLUB_API_AUTHKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_MSGCLUB_API_URL,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}
    public function getSMSContentType()	{
        return $this->scopeConfig->getValue(
            self::XML_MSGCLUB_CONTENTTYPE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function validateSmsConfig() {
        return $this->getApiUrl() && $this->getAuthKey() && $this->getApiSenderId() && $this->getSMSContentType();
    }
	
	public function callApiUrl($mobilenumbers,$message)
	{
        try
        {
            $url = $this->getApiUrl();
            $authkey = $this->getAuthKey();
            $senderid = $this->getApiSenderId();
            $smsContentType=$this->getSMSContentType();
            $message=urlencode($message);

            $ch = curl_init();
            if (!$ch)
            {
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt_array($ch, array(
                CURLOPT_URL => $url."?AUTH_KEY=$authkey&message=$message&senderId=$senderid&routeId=1&mobileNos=$mobilenumbers&smsContentType=$smsContentType",
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_HTTPHEADER => array(
                    "Cache-Control: no-cache"
                ),
            ));

            $curlresponse = curl_exec($ch); // execute

            if(curl_errno($ch))
            {
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