<?php
namespace Mirakl\Connector\Model\Tax;

use Magento\Tax\Api\Data\TaxDetailsItemInterface;

class Calculator
{
    /**
     * @param   TaxDetailsItemInterface $itemTaxDetails
     * @param   TaxDetailsItemInterface $baseItemTaxDetails
     * @param   array                   $taxes
     */
    public function addMiraklTaxesToTaxItems(
        TaxDetailsItemInterface $itemTaxDetails,
        TaxDetailsItemInterface $baseItemTaxDetails,
        array $taxes
    ) {
        if (empty($taxes)) {
            return;
        }

        foreach ($taxes as $tax) {
            $itemTaxDetails->setPriceInclTax($itemTaxDetails->getPriceInclTax() + $tax['amount']);
            $itemTaxDetails->setRowTotalInclTax($itemTaxDetails->getRowTotalInclTax() + $tax['amount']);
            $itemTaxDetails->setRowTax($itemTaxDetails->getRowTax() + $tax['amount']);
            $baseItemTaxDetails->setPriceInclTax($baseItemTaxDetails->getPriceInclTax() + $tax['base_amount']);
            $baseItemTaxDetails->setRowTotalInclTax($baseItemTaxDetails->getRowTotalInclTax() + $tax['base_amount']);
            $baseItemTaxDetails->setRowTax($baseItemTaxDetails->getRowTax() + $tax['base_amount']);
        }
    }
}