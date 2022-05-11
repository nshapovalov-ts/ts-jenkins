<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Model\Quote\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Magento\Quote\Model\Quote;
use Closure;

/**
 * Class CommonTaxCollectorPlugin
 */
class CommonTaxCollectorPlugin extends \Mirakl\Connector\Plugin\Model\Quote\Total\CommonTaxCollectorPlugin
{

    /**
     * @param CommonTaxCollector $subject
     * @param Closure $proceed
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return  CommonTaxCollector
     */
    public function aroundCollect(
        CommonTaxCollector $subject,
        Closure $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $proceed($quote, $shippingAssignment, $total);

        /** @var Address $shippingAddress */
        $address = $shippingAssignment->getShipping()->getAddress();
        if ($address->getAddressType() == Address::ADDRESS_TYPE_BILLING) {
            return $subject;
        }

        if (!$this->quoteHelper->isMiraklQuote($quote)) {
            return $subject;
        }

        $shippingDiscountAmount = $address->getShippingDiscountAmount();
        $baseShippingDiscountAmount = $address->getBaseShippingDiscountAmount();

        if ($address->getShippingAmount() && !empty($shippingDiscountAmount) && $shippingDiscountAmount > 0 && $total->getShippingDiscountAmount() != $shippingDiscountAmount) {
            $total->setShippingDiscountAmount($shippingDiscountAmount);
            $total->setBaseShippingDiscountAmount($baseShippingDiscountAmount);
            $total->setDiscountDescription($address->getDiscountDescription());
        }

        $discountAmount = $address->getDiscountAmount();
        if (!empty($discountAmount) && $discountAmount > 0) {
            $quote->setDiscountAmount($address->getDiscountAmount());
        }

        $isSubtotal = $subject instanceof \Magento\Tax\Model\Sales\Total\Quote\Subtotal\Interceptor;
        if ($isSubtotal) {
            $method = $shippingAssignment->getShipping()->getMethod();
            if (empty($method) || $method == '_') {
                $shippingAssignment->getShipping()->setMethod('freeshipping_freeshipping');
                $address->setShippingMethod('freeshipping_freeshipping');
            }
        }

        $total->setShippingAmountForDiscount($total->getShippingInclTax());
        $total->setBaseShippingAmountForDiscount($total->getBaseShippingInclTax());
        $address->setShippingAmount($total->getShippingAmount());
        $address->setBaseShippingAmount($total->getBaseShippingAmount());
        $address->setShippingInclTax($total->getShippingInclTax());
        $address->setBaseShippingInclTax($total->getBaseShippingInclTax());
        $address->setShippingInclTax($total->getShippingInclTax());
        $address->setBaseShippingInclTax($total->getBaseShippingInclTax());
        $address->setShippingAmountForDiscount($total->getShippingAmountForDiscount());
        $address->setBaseShippingAmountForDiscount($total->getBaseShippingAmountForDiscount());

        return $subject;
    }
}
