<?php

namespace Magecomp\Smspro\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use Magecomp\Smspro\Helper\Orderverify;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magecomp\Smspro\Helper\Customer as Helpercustomer;
use Magecomp\Smspro\Helper\Data as Helperdata;

class OrderVerifyConfigProvider implements ConfigProviderInterface
{
    protected $orderverify;
    protected $urlBuilder;
    protected $checkoutSession;
    protected $helperdata;
    protected $customersession;
    protected $helpercustomer;

    public function __construct(UrlInterface $urlBuilder,
                                Orderverify $orderverify,
                                Session $checkoutSession,
                                CustomerSession $customersession,
                                Helperdata $helperdata,
                                Helpercustomer $helpercustomer)
    {
        $this->urlBuilder = $urlBuilder;
        $this->orderverify = $orderverify;
        $this->helperdata = $helperdata;
        $this->checkoutSession = $checkoutSession;
        $this->customersession = $customersession;
        $this->helpercustomer = $helpercustomer;
    }

    public function getConfig()
    {
        return ['orderverify' => ['enabledModule' => $this->check(), 'mobileNumber' => $this->getMobileNumber(), 'paymentMethods' => $this->orderverify->isValidPaymentMethod()]];
    }

    /**
     * @return bool
     */
    public function check()
    {
        if (!$this->helperdata->isEnabled()) {
            return false;
        }
        if (!$this->orderverify->isUserOrderConfirmEnable()) {
            return false;
        }
        if ($this->customersession->isLoggedIn()) {
            $ismobverify = $this->customersession->getCustomer()->getIsmobverify();
            if ($ismobverify && $ismobverify == 1) {
                return false;
            }
        }
        $valid = $this->orderverify->isValidCustomerGroup();
        if ($valid) {
            return true;
        }
        return false;
    }

    public function getMobileNumber()
    {
        $telephone = "";
        if ($this->customersession->isLoggedIn()) {
            $mobile = $this->customersession->getCustomer()->getMobilenumber();
            if ($mobile != '' || $mobile != null) {
                return $mobile;
            }

        }
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getBillingAddress()) {
            $shippingAddress = $quote->getBillingAddress();
            $telephone = $shippingAddress->getTelephone();
        }
        return $telephone;
    }
}
