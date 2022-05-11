<?php

namespace Retailplace\ShippingEstimation\Block\Product\View;

use Mirakl\Core\Model\Shop;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;

class ShippingEstimation extends \Magento\Catalog\Block\Product\View
{
    /**
     * @var \Retailplace\ShippingEstimation\Helper\Data
     */
    protected $helper;

    /**
     * @var OfferHelper
     */
    protected $offerHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Retailplace\ShippingEstimation\Helper\Data $helper
     * @param OfferHelper $offerHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Retailplace\ShippingEstimation\Helper\Data $helper,
        OfferHelper $offerHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        $this->helper = $helper;
        $this->offerHelper = $offerHelper;
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
