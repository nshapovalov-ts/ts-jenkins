<?php
namespace Magecomp\Smspro\Model\Config;

use Magento\Framework\App\ObjectManager;
use Magecomp\Smspro\Helper\Apicall;

class Gateways extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    protected $apihelper;

    public function __construct(Apicall $apihelper,array $data = []) {
        $this->apihelper = $apihelper;
        parent::__construct($data);
    }

    public function toOptionArray()
    {
        foreach($this->apihelper->getSmsgatewaylist() as $key => $smsgateway)
        {
            $Smsgatewaymodel = ObjectManager::getInstance()->create($smsgateway);
            $options[] = ['value' => $key,'label' => $Smsgatewaymodel->getTitle()];
        }
        return $options;
    }
}