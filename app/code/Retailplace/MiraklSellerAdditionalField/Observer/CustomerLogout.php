<?php

namespace Retailplace\MiraklSellerAdditionalField\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;

class CustomerLogout implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->helper->clearCustomerAttributesFromSession();
    }
}
