<?php

/**
 * Retailplace_CheckoutOverride
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CheckoutOverride\Plugin;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\SalesRule\Model\Quote\Address\Total\ShippingDiscount;

/**
 * Class ChangeDiscountTitle
 */
class ChangeDiscountTitle
{
    /**
     * Remove Discount word from Totals Description
     *
     * @param \Magento\SalesRule\Model\Quote\Address\Total\ShippingDiscount $subject
     * @param array $result
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return array
     */
    public function afterFetch(ShippingDiscount $subject, array $result, Quote $quote, Total $total): array
    {
        if (!empty($result['title'])) {
            $description = $total->getDiscountDescription() ?: '';
            $result['title'] = is_string($description) && strlen($description)
                ? __($description)
                : __('Discount');
        }

        return $result;
    }
}
