<?php
namespace Mirakl\FrontendDemo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Invoice extends AbstractHelper
{
    /**
     * Filters specified invoice totals excluding amounts of Mirakl order specific amounts
     *
     * @param   \Magento\Sales\Model\Order\Invoice  $invoice
     * @return  $this
     */
    public function filterInvoiceTotals(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();

        $grandTotal = $invoice->getGrandTotal()
            - $order->getMiraklShippingFee()
            - $order->getMiraklShippingTaxAmount()
            - $order->getMiraklCustomShippingTaxAmount();
        $invoice->setGrandTotal($grandTotal);

        $baseGrandTotal = $invoice->getBaseGrandTotal()
            - $order->getMiraklBaseShippingFee()
            - $order->getMiraklBaseShippingTaxAmount()
            - $order->getMiraklBaseCustomShippingTaxAmount();
        $invoice->setBaseGrandTotal($baseGrandTotal);

        $invoice->setShippingAmount($invoice->getShippingAmount() - $order->getMiraklShippingFee());
        $invoice->setBaseShippingAmount($invoice->getBaseShippingAmount() - $order->getMiraklBaseShippingFee());

        $shippingInclTax = $invoice->getShippingInclTax()
            - $order->getMiraklShippingFee()
            - $order->getMiraklShippingTaxAmount()
            - $order->getMiraklCustomShippingTaxAmount();
        $invoice->setShippingInclTax($shippingInclTax);

        $baseShippingInclTax = $invoice->getBaseShippingInclTax()
            - $order->getMiraklBaseShippingFee()
            - $order->getMiraklBaseShippingTaxAmount()
            - $order->getMiraklBaseCustomShippingTaxAmount();
        $invoice->setBaseShippingInclTax($baseShippingInclTax);

        $taxAmount = $invoice->getTaxAmount()
            - $order->getMiraklShippingTaxAmount()
            - $order->getMiraklCustomShippingTaxAmount();
        $invoice->setTaxAmount($taxAmount);

        $baseTaxAmount = $invoice->getBaseTaxAmount()
            - $order->getMiraklBaseShippingTaxAmount()
            - $order->getMiraklBaseCustomShippingTaxAmount();
        $invoice->setBaseTaxAmount($baseTaxAmount);

        return $this;
    }
}
