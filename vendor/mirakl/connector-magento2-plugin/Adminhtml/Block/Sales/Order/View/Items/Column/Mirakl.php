<?php
namespace Mirakl\Adminhtml\Block\Sales\Order\View\Items\Column;

class Mirakl extends \Magento\Sales\Block\Adminhtml\Items\Column\DefaultColumn
{
    /**
     * @return  float
     */
    public function getItemBaseShippingPriceExclTax()
    {
        $item = $this->getItem();
        $order = $this->getOrder();

        if (!$order->getMiraklIsShippingInclTax()) {
            return $item->getMiraklBaseShippingFee();
        }

        return $item->getMiraklBaseShippingFee()
             - $item->getMiraklBaseShippingTaxAmount()
             - $item->getMiraklBaseCustomShippingTaxAmount();
    }

    /**
     * @return  float
     */
    public function getItemBaseShippingPriceInclTax()
    {
        $item = $this->getItem();
        $order = $this->getOrder();

        if ($order->getMiraklIsShippingInclTax()) {
            return $item->getMiraklBaseShippingFee();
        }

        return $item->getMiraklBaseShippingFee()
             + $item->getMiraklBaseShippingTaxAmount()
             + $item->getMiraklBaseCustomShippingTaxAmount();
    }

    /**
     * @return  float
     */
    public function getItemShippingPriceExclTax()
    {
        $item = $this->getItem();
        $order = $this->getOrder();

        if (!$order->getMiraklIsShippingInclTax()) {
            return $item->getMiraklShippingFee();
        }

        return $item->getMiraklShippingFee()
             - $item->getMiraklShippingTaxAmount()
             - $item->getMiraklCustomShippingTaxAmount();
    }

    /**
     * @return  float
     */
    public function getItemShippingPriceInclTax()
    {
        $item = $this->getItem();
        $order = $this->getOrder();

        if ($order->getMiraklIsShippingInclTax()) {
            return $item->getMiraklShippingFee();
        }

        return $item->getMiraklShippingFee()
             + $item->getMiraklShippingTaxAmount()
             + $item->getMiraklCustomShippingTaxAmount();
    }
}