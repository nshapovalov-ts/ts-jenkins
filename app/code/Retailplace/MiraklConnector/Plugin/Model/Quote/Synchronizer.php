<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

namespace Retailplace\MiraklConnector\Plugin\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Mirakl\Api\Helper\Shipping as Api;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Model\Quote\Cache;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\Core\Helper\ShippingZone as ShippingZoneHelper;
use Retailplace\MiraklQuote\Model\Checkout\ShippingRates;

class Synchronizer extends QuoteSynchronizer
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var ShippingRates
     */
    protected $miraklQuoteShippingRates;

    /**
     * Constructor
     *
     * @param \Mirakl\Api\Helper\Shipping $api
     * @param \Mirakl\Connector\Helper\Config $config
     * @param \Mirakl\Core\Helper\ShippingZone $shippingZoneHelper
     * @param \Mirakl\Connector\Model\Quote\OfferCollector $offerCollector
     * @param \Mirakl\Connector\Model\Quote\Cache $cache
     * @param \Magento\Tax\Model\Calculation $taxCalculation
     * @param \Retailplace\MiraklQuote\Model\Checkout\ShippingRates $miraklQuoteShippingRates
     */
    public function __construct(
        Api $api,
        Config $config,
        ShippingZoneHelper $shippingZoneHelper,
        OfferCollector $offerCollector,
        Cache $cache,
        TaxCalculation $taxCalculation,
        ShippingRates $miraklQuoteShippingRates
    ) {
        parent::__construct(
            $api,
            $config,
            $shippingZoneHelper,
            $offerCollector,
            $cache,
            $taxCalculation
        );

        $this->miraklQuoteShippingRates = $miraklQuoteShippingRates;
    }

    /**
     * Returns current quote items grouped by order (SH02)
     *
     * @param QuoteSynchronizer $subject
     * @param callable $proceed
     * @param CartInterface $quote
     * @return array|mixed|null
     */
    public function aroundGetGroupedItems(QuoteSynchronizer $subject, callable $proceed, CartInterface $quote)
    {
        $hash = $subject->cache->getQuoteControlHash($quote);
        if ($cache = $this->cache->getCachedMethodResult(__METHOD__, $quote->getId(), $hash)) {
            return $cache;
        }

        $groupedItems = $this->offerCollector->getItemsWithoutOffer($quote);
        $itemsWithOffer = $this->offerCollector->getItemsWithOffer($quote);

        $groupedItems = array_merge($groupedItems, $itemsWithOffer);

        $this->cache->setCachedMethodResult(__METHOD__, $quote->getId(), $groupedItems, $hash);

        return $groupedItems;
    }

    /**
     * Generate Shipping Rates depends on Mirakl Quote Data
     *
     * @param \Mirakl\Connector\Model\Quote\Synchronizer $subject
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param bool $useCache
     * @param int|null $cacheLifetime
     * @return array
     */
    public function beforeGetShippingFees(
        QuoteSynchronizer $subject,
        CartInterface $quote,
        $useCache = false,
        $cacheLifetime = null
    ): array {
        $this->miraklQuoteShippingRates->addMiraklShippingRates($quote);

        return [$quote, $useCache, $cacheLifetime];
    }
}
