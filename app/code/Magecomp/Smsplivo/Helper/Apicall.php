<?php 
namespace Magecomp\Smsplivo\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_PLIVO_AUTH_ID = 'smspro/smsgatways/plivoauthid';
    const XML_PLIVO_AUTH_TOKEN = 'smspro/smsgatways/plivoauthtoken';
	const XML_PLIVO_SENDER_ID = 'smspro/smsgatways/plivosenderid';

	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("Plivo");
    }

    public function getAuthId(){
        return $this->scopeConfig->getValue(
            self::XML_PLIVO_AUTH_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAuthToken()	{
        return $this->scopeConfig->getValue(
            self::XML_PLIVO_AUTH_TOKEN,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getSenderId()	{
return $this->scopeConfig->getValue(
            self::XML_PLIVO_SENDER_ID,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}

	public function validateSmsConfig() {
        return $this->getSenderId() && $this->getAuthToken() && $this->getAuthId();
    }

	public function callApiUrl($mobilenumbers,$message)
	{
        try
        {
            $url = 'https://api.plivo.com/v1/Account/'.$this->getAuthId().'/Message/';
            $data = array("src" => $this->getSenderId(), "dst" => $mobilenumbers, "text" => $message);

            $header = array();
            $header[] = 'Content-type: application/json';
            $header[] = 'charset: utf-8';
            //$header[] = 'Content-Length: 95';

            $data_string = json_encode($data);
            $ch = curl_init($url);
            if (!$ch)
            {
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->getAuthId().":".$this->getAuthToken());
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);

            if (curl_error($ch)) {
                curl_close($ch);
                return 'Error: '.curl_error($ch);
            }

            curl_close($ch);
            return true;
            //$info = curl_getinfo($ch);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
	}
}