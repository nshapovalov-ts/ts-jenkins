<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class InitOrderItemsInvoicedAmountsObserver implements ObserverInterface
{
    /**
     * Define some default invoice amounts on Mirakl orders because when we are invoicing some order items that are not
     * Mirakl offers, Magento uses some invoice amounts to build the invoice totals and takes the order grand total
     * including Mirakl offers that are not invoiced in Magento but in Mirakl platform (by the shops).
     *
     * @see \Magento\Sales\Model\Order\Invoice\Total\Subtotal ($allowedSubtotal)
     * @see \Magento\Sales\Model\Order\Invoice\Total\Tax ($allowedTax)
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order && $order->isObjectNew() && !$order->getSubtotalInvoiced()) {
            foreach ($order->getAllItems() as $item) {
                /** @var \Magento\Sales\Model\Order\Item $item */
                if ($item->getMiraklOfferId()) {
                    $order->setSubtotalInvoiced($order->getSubtotalInvoiced() + $item->getRowTotal());
                    $order->setBaseSubtotalInvoiced($order->getBaseSubtotalInvoiced() + $item->getBaseRowTotal());
                    $order->setTaxInvoiced($order->getTaxInvoiced() + $item->getTaxAmount());
                    $order->setBaseTaxInvoiced($order->getBaseTaxInvoiced() + $item->getBaseTaxAmount());
                }
            }
        }
    }
}