<?php

namespace Magecomp\Smspro\Block\Customer;

class Checkout extends \Magento\Framework\View\Element\Template
{
    protected $helpercustomer;
    protected $_urlManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magecomp\Smspro\Helper\Customer $helpercustomer,
        \Magento\Framework\UrlInterface $urlManager,
        array $data = [] )
    {
        $this->helpercustomer = $helpercustomer;
        $this->_urlManager = $urlManager;
        parent::__construct($context, $data);
    }

    public function getButtonclass()
    {
        return $this->helpercustomer->getButtonclass();
    }

    public function getCheckoutURL()
    {
        return $this->_urlManager->getUrl('checkout/index/index', ['_secure' => true]);
    }

    public function getDefaultContry()
    {
        return $this->helpercustomer->getDefaultcontry();
    }
}