<?php

/**
 * Retailplace_ConfigurableProductVolumePrice
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ConfigurableProductVolumePrice\Block\Product\Offer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Tax\Model\CalculationFactory as TaxCalculationFactory;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\FrontendDemo\Block\Product\Offer\PriceRanges as MiraklPriceRanges;
use Mirakl\FrontendDemo\Helper\Tax as TaxHelper;
use Retailplace\ConfigurableProductVolumePrice\Model\OfferManagement;

/**
 * Class PriceRanges
 */
class PriceRanges extends MiraklPriceRanges
{
    /** @var string */
    protected $_template = 'Mirakl_FrontendDemo::product/offer/price_ranges.phtml';

    /** @var \Retailplace\ConfigurableProductVolumePrice\Model\OfferManagement */
    private $offerManagement;

    /**
     * PriceRanges constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Mirakl\Connector\Helper\Config $connectorConfig
     * @param \Mirakl\FrontendDemo\Helper\Tax $taxHelper
     * @param \Magento\Tax\Model\CalculationFactory $taxCalculationFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Retailplace\ConfigurableProductVolumePrice\Model\OfferManagement $offerManagement
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConnectorConfig $connectorConfig,
        TaxHelper $taxHelper,
        TaxCalculationFactory $taxCalculationFactory,
        PriceCurrencyInterface $priceCurrency,
        OfferManagement $offerManagement,
        array $data = []
    ) {
        parent::__construct($context, $connectorConfig, $taxHelper, $taxCalculationFactory, $priceCurrency, $data);
        $this->offerManagement = $offerManagement;
    }

    /**
     * Get Product Offer Price Ranges
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Mirakl\Connector\Model\Offer[]
     */
    public function getProductPriceRanges(ProductInterface $product): array
    {
        return $this->offerManagement->getProductPriceRanges($product);
    }

    /**
     * Get Array of Offer Price Ranges Sorted by Sku
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return array
     */
    public function getSkuPriceRanges(ProductInterface $product): array
    {
        $productOffersData = $this->offerManagement->getProductPriceRanges($product);
        $ranges = [];
        foreach ($productOffersData as $sku => $data) {
            $ranges[$sku] = $data['price_ranges'];
        }

        return $ranges;
    }

    /**
     * Get Array of Offer Discounts Sorted by Sku
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return array
     */
    public function getSkuDiscountRanges(ProductInterface $product): array
    {
        $productOffersData = $this->offerManagement->getProductPriceRanges($product);
        $ranges = [];
        foreach ($productOffersData as $sku => $data) {
            $ranges[$sku] = $data['discount_ranges'];
        }

        return $ranges;
    }

    /**
     * Get Array of Products (list of Configurable Simples or Single Simple)
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Magento\Catalog\Api\Data\ProductInterface[]
     */
    public function getSimpleProductsList(ProductInterface $product): array
    {
        return $this->offerManagement->getSimpleProductsList($product);
    }
}
