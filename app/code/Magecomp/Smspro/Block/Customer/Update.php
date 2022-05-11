<?php

namespace Magecomp\Smspro\Block\Customer;

class Update extends \Magento\Framework\View\Element\Template
{
    protected $helpercustomer;
    protected $customersession;
    protected $helperdata;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magecomp\Smspro\Helper\Customer $helpercustomer,
        \Magento\Customer\Model\Session $customersession,
        \Magecomp\Smspro\Helper\Data $helperdata,
        array $data = [] )
    {
        $this->helpercustomer = $helpercustomer;
        $this->customersession = $customersession;
        $this->helperdata = $helperdata;
        parent::__construct($context, $data);
    }

    public function getButtonclass()
    {
        return $this->helpercustomer->getButtonclass();
    }

    public function getCustomerMobile()
    {
        if ($this->customersession->isLoggedIn()) {
            return $this->customersession->getCustomer()->getMobilenumber();
        }
    }

    public function getDefaultContry()
    {
        return $this->helpercustomer->getDefaultcontry();
    }
    public function getMindigits()
    {
        return $this->helperdata->getMindigits();
    }
    public function getMaxdigits()
    {
        return $this->helperdata->getMaxdigits();
    }
}