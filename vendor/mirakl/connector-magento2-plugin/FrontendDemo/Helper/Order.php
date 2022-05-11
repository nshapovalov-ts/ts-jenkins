<?php
namespace Mirakl\FrontendDemo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Api\Helper\Order as OrderApi;
use Mirakl\MMP\Common\Domain\Evaluation;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class Order extends AbstractHelper
{
    /**
     * @var OrderApi
     */
    protected $orderApi;

    /**
     * @var array
     */
    protected $evaluations = [];

    /**
     * @param   Context     $context
     * @param   OrderApi    $orderApi
     */
    public function __construct(Context $context, OrderApi $orderApi)
    {
        parent::__construct($context);
        $this->orderApi = $orderApi;
    }

    /**
     * Filters specified order totals excluding amounts of Mirakl orders
     *
     * @param   \Magento\Sales\Model\Order  $order
     * @return  $this
     */
    public function filterOrderTotals(\Magento\Sales\Model\Order $order)
    {
        $grandTotal = $order->getGrandTotal() - $order->getMiraklShippingFee();
        $baseGrandTotal = $order->getBaseGrandTotal() - $order->getMiraklBaseShippingFee();

        $shippingExclTax = $order->getShippingAmount() - $order->getMiraklShippingFee();
        $baseShippingExclTax = $order->getBaseShippingAmount() - $order->getMiraklBaseShippingFee();

        $shippingInclTax = $order->getShippingInclTax() - $order->getMiraklShippingFee();
        $baseShippingInclTax = $order->getBaseShippingInclTax() - $order->getMiraklBaseShippingFee();

        if (!$order->getMiraklIsShippingInclTax()) {
            // Mirakl shipping price is EXCLUDING tax
            $grandTotal = $grandTotal
                - $order->getMiraklShippingTaxAmount()
                - $order->getMiraklCustomShippingTaxAmount();
            $baseGrandTotal = $baseGrandTotal
                - $order->getMiraklBaseShippingTaxAmount()
                - $order->getMiraklBaseCustomShippingTaxAmount();

            $shippingInclTax = $shippingInclTax
                - $order->getMiraklShippingTaxAmount()
                - $order->getMiraklCustomShippingTaxAmount();
            $baseShippingInclTax = $baseShippingInclTax
                - $order->getMiraklBaseShippingTaxAmount()
                - $order->getMiraklBaseCustomShippingTaxAmount();
        } else {
            // Mirakl shipping price is INCLUDING tax
            $shippingExclTax = $shippingExclTax
                + $order->getMiraklShippingTaxAmount()
                + $order->getMiraklCustomShippingTaxAmount();
            $baseShippingExclTax = $baseShippingExclTax
                + $order->getMiraklBaseShippingTaxAmount()
                + $order->getMiraklBaseCustomShippingTaxAmount();
        }

        $order->setGrandTotal($grandTotal);
        $order->setBaseGrandTotal($baseGrandTotal);
        $order->setShippingAmount($shippingExclTax);
        $order->setBaseShippingAmount($baseShippingExclTax);
        $order->setShippingInclTax($shippingInclTax);
        $order->setBaseShippingInclTax($baseShippingInclTax);

        foreach ($order->getItemsCollection() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if ($item->getMiraklOfferId()) {
                $order->setSubtotal($order->getSubtotal() - $item->getRowTotal());
                $order->setBaseSubtotal($order->getBaseSubtotal() - $item->getBaseRowTotal());

                $order->setSubtotalInclTax($order->getSubtotalInclTax() - $item->getRowTotalInclTax());
                $order->setBaseSubtotalInclTax($order->getBaseSubtotalInclTax() - $item->getBaseRowTotalInclTax());

                $order->setGrandTotal($order->getGrandTotal() - $item->getRowTotalInclTax());
                $order->setBaseGrandTotal($order->getBaseGrandTotal() - $item->getBaseRowTotalInclTax());

                $taxAmount = $order->getTaxAmount()
                    - $item->getTaxAmount()
                    - $item->getMiraklShippingTaxAmount()
                    - $item->getMiraklCustomShippingTaxAmount();
                $order->setTaxAmount($taxAmount);

                $baseTaxAmount = $order->getBaseTaxAmount()
                    - $item->getBaseTaxAmount()
                    - $item->getMiraklBaseShippingTaxAmount()
                    - $item->getMiraklBaseCustomShippingTaxAmount();
                $order->setBaseTaxAmount($baseTaxAmount);
            }
        }

        return $this;
    }

    /**
     * @param   MiraklOrder $miraklOrder
     * @return  Evaluation
     * @throws  \Exception
     */
    public function getOrderEvaluation(MiraklOrder $miraklOrder)
    {
        if (!isset($this->evaluations[$miraklOrder->getId()])) {
            try {
                $this->evaluations[$miraklOrder->getId()] = $this->orderApi->getOrderEvaluation($miraklOrder);
            } catch (\Exception $e) {
                // Ignore Not Found exception
                if ($e->getCode() != 404) {
                    throw $e;
                }
                $this->evaluations[$miraklOrder->getId()] = null;
            }
        }

        return $this->evaluations[$miraklOrder->getId()];
    }
}
