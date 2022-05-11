<?php
namespace ShipperHQ\AddressAutocomplete\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    public function getConfigValue($configField, $store = null)
    {
        return $this->scopeConfig->getValue(
            $configField,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

}
