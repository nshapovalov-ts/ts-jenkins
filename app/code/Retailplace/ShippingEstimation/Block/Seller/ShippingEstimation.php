<?php

namespace Retailplace\ShippingEstimation\Block\Seller;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Mirakl\Core\Model\Shop;

class ShippingEstimation extends \Mirakl\FrontendDemo\Block\Shop\View
{
    /**
     * @var PriceCurrencyInterface $priceCurrency
     */
    protected $priceCurrency;

    /**
     * @var \Retailplace\ShippingEstimation\Helper\Data
     */
    protected $helper;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param \Retailplace\ShippingEstimation\Helper\Data $helper
     * @param PriceCurrencyInterface $priceCurrency
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Retailplace\ShippingEstimation\Helper\Data $helper,
        PriceCurrencyInterface $priceCurrency,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->helper = $helper;
        $this->priceCurrency = $priceCurrency;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Shop $shop
     * @return DataObject
     */
    public function getFreeShippingData($shop)
    {
        return $this->helper->getFreeShippingData($shop);
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->helper->isLoggedIn();
    }
}
