<?php
namespace Mirakl\FrontendDemo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;

class InvalidateCartCookieObserver implements ObserverInterface
{
    /**
     * @var PhpCookieManager
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @param   PhpCookieManager        $cookieManager
     * @param   CookieMetadataFactory   $cookieMetadataFactory
     */
    public function __construct(PhpCookieManager $cookieManager, CookieMetadataFactory $cookieMetadataFactory)
    {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * Invalidates the cookie for minicart data refresh
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $sectionDataIds = json_decode($this->cookieManager->getCookie('section_data_ids', '{}'), true);
        if (isset($sectionDataIds['cart'])) {
            $sectionDataIds['cart'] += 1000;
            $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
            $this->cookieManager->setPublicCookie('section_data_ids', json_encode($sectionDataIds), $cookieMetadata);
        }
    }
}
