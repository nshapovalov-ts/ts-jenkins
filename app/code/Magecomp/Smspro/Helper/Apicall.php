<?php
namespace Magecomp\Smspro\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ObjectManager;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SMSGATEWAY ='smspro/smsgatways/gateway';

    protected $smsgatewaylist;
    protected $_storeManager;

	public function __construct(\Magento\Framework\App\Helper\Context $context,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                array $smsgatewaylist = [])
	{
        $this->smsgatewaylist = $smsgatewaylist;
        $this->_storeManager = $storeManager;
		parent::__construct($context);
	}

    public function getStoreid()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getSmsgatewaylist()
    {
        return $this->smsgatewaylist;
    }

    public function getSelectedGateway() {
        return $this->scopeConfig->getValue(self::XML_PATH_SMSGATEWAY,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreid());
    }

    public function getSelectedGatewayModel()
    {
        if($this->getSelectedGateway() != '' || $this->getSelectedGateway() != null)
        {
            $Selectedgateway = $this->smsgatewaylist[$this->getSelectedGateway()];
            return ObjectManager::getInstance()->create($Selectedgateway);
        }
        else
            return null;
    }

	public function callApiUrl($mobilenumbers,$message,$dltid=null)
	{

        $curentsmsModel = $this->getSelectedGatewayModel();


        if($curentsmsModel == '' || $curentsmsModel == null){
            return __("SMS Gateway haven't configured yet.");
        }

        if(!$curentsmsModel->validateSmsConfig()){
            return __("Please, Make Sure You have Configured SMS Gateway Properly.");
        }

        if($curentsmsModel->getTitle() == 'Msg91')
        {
            return $curentsmsModel->callApiUrl($mobilenumbers,$message,$dltid);
        }

        return $curentsmsModel->callApiUrl($mobilenumbers,$message);
	}
}
