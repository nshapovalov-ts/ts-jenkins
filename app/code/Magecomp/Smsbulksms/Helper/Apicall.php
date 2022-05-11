<?php 
namespace Magecomp\Smsbulksms\Helper;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_BULKSMS_API_USERNAME = 'smspro/smsgatways/bulksmsusername';
	const XML_BULKSMS_API_PASSWORD = 'smspro/smsgatways/bulksmspassword';
	const XML_BULKSMS_API_URL = 'smspro/smsgatways/bulksmsapiurl';

	public function __construct(\Magento\Framework\App\Helper\Context $context)
	{
		parent::__construct($context);
	}

    public function getTitle() {
        return __("Bulksms");
    }

    public function getApiUsername(){
        return $this->scopeConfig->getValue(
            self::XML_BULKSMS_API_USERNAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getApiPassword(){
        return $this->scopeConfig->getValue(
            self::XML_BULKSMS_API_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

	public function getApiUrl()	{
return $this->scopeConfig->getValue(
            self::XML_BULKSMS_API_URL,
			 \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
	}

    public function validateSmsConfig()
    {
        return $this->getApiUsername() && $this->getApiPassword() && $this->getApiUrl();
    }

    public function callApiUrl($mobilenumbers,$message)
    {
        try
        {
            $url = $this->getApiUrl();
            $user = $this->getApiUsername();
            $password = $this->getApiPassword();

            $ch = curl_init();
            if (!$ch){
                return "Couldn't initialize a cURL handle";
            }
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt ($ch, CURLOPT_POSTFIELDS,
                "username=$user&password=$password&message=$message&msisdn=$mobilenumbers");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $curlresponse = curl_exec($ch); // execute
            $curl_info = curl_getinfo($ch);

            if ($curlresponse == FALSE)
            {
                return "cURL error: ".curl_error($ch);
            }
            elseif($curl_info['http_code'] != 200)
            {
                return "Error: non-200 HTTP status code: ".$curl_info['http_code'];
            }
            else
            {
                $api_result = explode( '|', $curlresponse);
                $status_code = $api_result[0];
                if($status_code == '0' || $status_code == '1')
                {
                    return true;
                }
                return "Error sending: status code [".$api_result[0]."] description [".$api_result[1]."]";
            }
            curl_close($ch);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}