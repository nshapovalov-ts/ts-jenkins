<?php

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Plugin;

use Magento\Quote\Api\Data\CartInterface;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;

class MiraklQuoteHelper
{
    /**
     * Quotes for Mirakl Quote Request should always return true
     *
     * @param \Mirakl\Connector\Helper\Quote $subject
     * @param bool $result
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return bool
     */
    public function afterIsFullMiraklQuote(QuoteHelper $subject, bool $result, CartInterface $quote): bool
    {
        if ($quote->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)) {
            $result = true;
        }

        return $result;
    }
}
