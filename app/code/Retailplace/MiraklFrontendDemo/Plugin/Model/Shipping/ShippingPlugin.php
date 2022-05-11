<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Model\Shipping;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\Rate\CarrierResult;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Shipping;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Closure;

/**
 * Class ShippingPlugin
 */
class ShippingPlugin
{
    /**
     * @var CarrierFactory
     */
    protected $carrierFactory;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @param CarrierFactory $carrierFactory
     * @param QuoteHelper $quoteHelper
     */
    public function __construct(CarrierFactory $carrierFactory, QuoteHelper $quoteHelper)
    {
        $this->carrierFactory = $carrierFactory;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * Around Collect Carrier Rates
     *
     * @param Shipping $subject
     * @param Closure $proceed
     * @param string $carrierCode
     * @param RateRequest $request
     * @return  Shipping
     */
    public function aroundCollectCarrierRates(
        Shipping $subject,
        Closure $proceed,
        string $carrierCode,
        RateRequest $request
    ) {
        $quote = $this->extractQuoteFromItems($request->getAllItems());

        if ($carrierCode == 'freeshipping' && $quote && $this->quoteHelper->isFullMiraklQuote($quote)) {
            $carrier = $this->carrierFactory->create($carrierCode, $request->getStoreId());
            $result = $subject->getResult();
            /** @var Result $rates */
            $rates = $carrier->collectRates($request);
            $miraklShippingFee = $quote->getData('mirakl_shipping_fee');
            if ($rates instanceof Result && !empty($miraklShippingFee)) {
                foreach ($rates->getAllRates() as $rate) {
                    $rate->setPrice($miraklShippingFee);
                }
            }

            if ($result instanceof Result) {
                $result->append($rates);
            } elseif ($result instanceof CarrierResult) {
                $result->appendResult($rates, $carrier->getConfigData('showmethod') != 0);
            }

            return $subject;
        }

        return $proceed($carrierCode, $request);
    }

    /**
     * Extract Quote From Items
     *
     * @param CartItemInterface[] $items
     * @return  CartInterface|null
     */
    private function extractQuoteFromItems(array $items): ?CartInterface
    {
        foreach ($items as $item) {
            if ($quote = $item->getQuote()) {
                return $quote;
            }
        }

        return null;
    }
}
