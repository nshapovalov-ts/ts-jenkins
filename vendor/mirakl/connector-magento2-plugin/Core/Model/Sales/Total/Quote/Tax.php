<?php
namespace Mirakl\Core\Model\Sales\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

class Tax extends \Magento\Tax\Model\Sales\Total\Quote\Tax
{
    /**
     * {@inheritdoc}
     */
    protected function processShippingTaxInfo(
        ShippingAssignmentInterface $shippingAssignment,
        QuoteAddress\Total $total,
        $shippingTaxDetails,
        $baseShippingTaxDetails
    ) {
        $quote = $shippingAssignment->getShipping()->getAddress()->getQuote();
        $customShippingTaxAmount = $quote->getMiraklCustomShippingTaxAmount();
        $customShippingTaxBaseAmount = $quote->getMiraklBaseCustomShippingTaxAmount();

        if ($customShippingTaxAmount || $customShippingTaxBaseAmount) {
            // Add Mirakl custom shipping taxes if applicable
            $shippingTaxDetails->setPriceInclTax($shippingTaxDetails->getPriceInclTax() + $customShippingTaxAmount);
            $shippingTaxDetails->setRowTotalInclTax($shippingTaxDetails->getRowTotalInclTax() + $customShippingTaxAmount);
            $shippingTaxDetails->setRowTax($shippingTaxDetails->getRowTax() + $customShippingTaxAmount);

            $baseShippingTaxDetails->setPriceInclTax($baseShippingTaxDetails->getPriceInclTax() + $customShippingTaxBaseAmount);
            $baseShippingTaxDetails->setRowTotalInclTax($baseShippingTaxDetails->getRowTotalInclTax() + $customShippingTaxBaseAmount);
            $baseShippingTaxDetails->setRowTax($baseShippingTaxDetails->getRowTax() + $customShippingTaxBaseAmount);
        }

        return parent::processShippingTaxInfo($shippingAssignment, $total, $shippingTaxDetails, $baseShippingTaxDetails);
    }
}