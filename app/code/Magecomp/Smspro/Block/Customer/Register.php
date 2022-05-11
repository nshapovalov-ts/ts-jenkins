<?php
namespace Magecomp\Smspro\Block\Customer;

class Register extends \Magento\Framework\View\Element\Template
{
    protected $helpercustomer;
    protected $helperdata;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magecomp\Smspro\Helper\Customer $helpercustomer,
        \Magecomp\Smspro\Helper\Data $helperdata,
        array $data = [] )
    {
        $this->helpercustomer = $helpercustomer;
        $this->helperdata = $helperdata;
        parent::__construct($context, $data);
    }

    public function getButtonclass()
    {
        return $this->helpercustomer->getButtonclass();
    }

    public function IsSignUpConfirmation()
    {
        return $this->helpercustomer->isSignUpConfirmationForUser();
    }

    public function getDefaultContry()
    {
        return $this->helperdata->getDefaultcontry();
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