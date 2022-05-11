<?php

namespace StripeIntegration\Payments\Helper;

use StripeIntegration\Payments\Helper\Logger;
use StripeIntegration\Payments\Exception\WebhookException;

class TaxHelper
{
    public function __construct(
    ) {
    }

    public function getShippingTaxRatesFromQuote($quote)
    {
        if ($quote->getIsVirtual())
            return [];

        $shippingAddress = $quote->getShippingAddress();
        if (empty($shippingAddress))
            return [];

        return $shippingAddress->getAppliedTaxes();
    }

    public function getShippingTaxPercentFromRate($rate)
    {
        if (empty($rate['percent']))
            return 0;

        if (is_numeric($rate['percent']) && $rate['percent'] > 0)
            return $rate['percent'] / 100;

        return 0;
    }

    public function getBaseShippingAmountForQuoteItem($quoteItem, $quote)
    {
        if ($quote->getIsVirtual())
            return 0;

        if ($quoteItem->getProductType() == "virtual" || $quoteItem->getProductType() == "downloadable")
            return 0;

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->requestShippingRates($quoteItem);
        return $quoteItem->getBaseShippingAmount();
    }

    public function getBaseShippingTaxFor($quoteItem, $quote)
    {
        if ($quote->getIsVirtual())
            return 0;

        $baseShippingAmount = $this->getBaseShippingAmountForQuoteItem($quoteItem, $quote);
        if ($baseShippingAmount == 0)
            return 0;

        $tax = 0;
        $rates = $this->getShippingTaxRatesFromQuote($quote);
        foreach ($rates as $rate)
        {
            $percent = $this->getShippingTaxPercentFromRate($rate);
            $tax += round($baseShippingAmount * $percent, 2);
        }

        return $tax;
    }
}
