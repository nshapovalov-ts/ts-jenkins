<?php
namespace Retailplace\Theme\Plugin;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\StoreManagerInterface;

class StoreCodeBodyClassPlugin implements ObserverInterface
{
    protected $config;
    protected $storeManager;
    protected $customerSession;

    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->customerSession  = $customerSessionFactory->create();
    }

    public function execute(Observer $observer)
    {
        $store = $this->storeManager->getStore();
        $storeCode = $store->getCode();
        $websiteCode = $store->getWebsite()->getCode();
        $this->config->addBodyClass($storeCode);
        $this->config->addBodyClass($websiteCode);
        if ($this->customerSession->isLoggedIn()) {
            $this->config->addBodyClass('is_customer_login');
        }
    }
}
