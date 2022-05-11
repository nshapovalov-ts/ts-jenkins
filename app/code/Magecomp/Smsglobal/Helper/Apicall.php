<?php 
namespace Magecomp\Smsglobal\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_SMSGLOBAL_API_KEY = 'smspro/smsgatways/smsglobalapikey';
    const XML_SMSGLOBAL_API_SECRATE = 'smspro/smsgatways/smsglobalapisecrate';
	const XML_SMSGLOBAL_API_URL = 'smspro/smsgatways/smsglobalapiurl';
    const XML_SMSGLOBAL_ALLOWSTRING = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTWXYZ0123456789';

	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("SmsGlobal");
    }

    public function getApiKey(){
        return $this->scopeConfig->getValue(
            self::XML_SMSGLOBAL_API_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getApiSecrate()	{
        return $this->scopeConfig->getValue(
            self::XML_SMSGLOBAL_API_SECRATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_SMSGLOBAL_API_URL,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}

	public function validateSmsConfig() {
        return $this->getApiUrl() && $this->getApiSecrate() && $this->getApiKey();
    }

    public function getAllowString($allowlength = 10)
    {
        $returnvalue = '';
        $size = strlen(self::XML_SMSGLOBAL_ALLOWSTRING);
        for ($i = 0; $i < $allowlength; $i++) {
            $returnvalue .= self::XML_SMSGLOBAL_ALLOWSTRING[rand(0, $size - 1)];
        }
        return $returnvalue;
    }

    public function generateRandomHeader()
    {
        $method = 'POST';
        $url = '/v2/smspro/';
        $host = 'api.smsglobal.com';
        $port = 80;
        $AddData = '';
        $timestamp = time();
        $allowstring = $this->getAllowString();
        $rawString = $timestamp . "\n" . $allowstring . "\n" . $method . "\n" . $url . "\n" . $host . "\n" . $port . "\n" . $AddData . "\n";
        $hashHeader = base64_encode(hash_hmac('sha256', $rawString, $this->getApiSecrate(), true));

        return "MAC id=\"{$this->getApiKey()}\", ts=\"{$timestamp}\", nonce=\"{$allowstring}\", mac=\"{$hashHeader}\"";
    }
	
	public function callApiUrl($mobilenumbers,$message)
	{
        try
        {
            $newheader = $this->generateRandomHeader();

            $ch = curl_init();
            if (!$ch)
            {
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt($ch, CURLOPT_URL, $this->getApiUrl());
            curl_setopt($ch,CURLOPT_HTTPHEADER, [
                "Authorization: $newheader",
                'Content-Type: application/json',
                'Accept: application/json'
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'destination' => $mobilenumbers,
                'message' => $message
            ]));

            $result = curl_exec($ch);

            if (curl_error($ch)) {
                curl_close($ch);
                return 'Error: '.curl_error($ch);
            }

            curl_close($ch);
            return true;
            //json_decode($result);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
	}
}