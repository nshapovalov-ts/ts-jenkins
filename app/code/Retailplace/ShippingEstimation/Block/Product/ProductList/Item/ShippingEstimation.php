<?php

namespace Retailplace\ShippingEstimation\Block\Product\ProductList\Item;

use Magento\Catalog\Block\Product\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Mirakl\Core\Model\Shop;

class ShippingEstimation extends \Magento\Catalog\Block\Product\ProductList\Item\Block
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
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @param Context $context
     * @param \Retailplace\ShippingEstimation\Helper\Data $helper
     * @param OfferHelper $offerHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Retailplace\ShippingEstimation\Helper\Data $helper,
        OfferHelper $offerHelper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->offerHelper = $offerHelper;
        $this->priceCurrency = $priceCurrency;
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
     * @param float $amount
     * @return string
     */
    public function getFormattedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }

    /**
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->helper->isLoggedIn();
    }

    /**
     * Get offer helper
     *
     * @return  OfferHelper
     */
    public function getOfferHelper()
    {
        return $this->offerHelper;
    }
}
