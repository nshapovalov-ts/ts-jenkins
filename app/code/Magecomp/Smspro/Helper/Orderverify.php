<?php

namespace Magecomp\Smspro\Helper;


use Magento\Store\Model\ScopeInterface;

class Orderverify extends \Magecomp\Smspro\Helper\Data
{
    const CONFIG_ORDERVERIFY_ACTIVE = 'usertemplate/userorderconfirm/enable';
    const CONFIG_ORDERVERIFY_GROUPS = 'usertemplate/userorderconfirm/customer_groups';
    const CONFIG_ORDERVERIFY_PAYMENT = 'usertemplate/userorderconfirm/payment_method';

    public function isValidCustomerGroup()
    {
        $customerGroups = explode(",", $this->scopeConfig->getValue(self::CONFIG_ORDERVERIFY_GROUPS, ScopeInterface::SCOPE_STORE));
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        if (in_array($customerGroupId, $customerGroups)) {
            return true;
        }
        return false;
    }

    public function isValidPaymentMethod()
    {
        $selectedMethods = explode(",", $this->scopeConfig->getValue(self::CONFIG_ORDERVERIFY_PAYMENT, ScopeInterface::SCOPE_STORE));
        return $selectedMethods;
    }

    public function isUserOrderConfirmEnable()
    {
        return $this->scopeConfig->getValue(self::CONFIG_ORDERVERIFY_ACTIVE, ScopeInterface::SCOPE_STORE);
    }
}
