<?php 
namespace Magecomp\Smstextlocal\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_TEXTLOCAL_API_SENDERID = 'smspro/smsgatways/textlocalsenderid';
    const XML_TEXTLOCAL_API_AUTHKEY = 'smspro/smsgatways/textlocalauthkey';
	const XML_TEXTLOCAL_API_URL = 'smspro/smsgatways/textlocalapiurl';


	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("Textlocal");
    }

    public function getApiSenderId(){
        return $this->scopeConfig->getValue(
            self::XML_TEXTLOCAL_API_SENDERID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAuthKey()	{
        return $this->scopeConfig->getValue(
            self::XML_TEXTLOCAL_API_AUTHKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

 	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_TEXTLOCAL_API_URL,
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

            $apiKey = urlencode($authkey);

            // Message details
            $numbers = array($mobilenumbers);
            $sender = urlencode($senderid);
            $message = rawurlencode($message);

            $numbers = implode(',', $numbers);

            // Prepare data for POST request
            $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

            $ch = curl_init();
            if (!$ch)
            {
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt ($ch, CURLOPT_POST, true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,$data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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