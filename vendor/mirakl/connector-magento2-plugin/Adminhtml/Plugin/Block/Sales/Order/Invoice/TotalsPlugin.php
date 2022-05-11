<?php
namespace Mirakl\Adminhtml\Plugin\Block\Sales\Order\Invoice;

use Magento\Sales\Block\Order\Totals;
use Mirakl\Connector\Helper\Order as OrderHelper;

class TotalsPlugin
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param OrderHelper $orderHelper
     */
    public function __construct(OrderHelper $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param   Totals  $subject
     * @param   array   $totals
     * @return  array
     */
    public function afterGetTotals(Totals $subject, $totals)
    {
        $order = $subject->getParentBlock()->getOrder();

        if (!empty($totals) && $order && $this->orderHelper->isMiraklOrder($order)) {
            foreach ($totals as &$total) {
                switch ($total['code']) {
                    case 'shipping':
                        $total['value'] = $this->orderHelper->getOperatorShippingExclTax($order);
                        $total['base_value'] = $this->orderHelper->getOperatorBaseShippingExclTax($order);
                        break;
                    case 'shipping_incl':
                        $total['value'] = $this->orderHelper->getOperatorShippingInclTax($order);
                        $total['base_value'] = $this->orderHelper->getOperatorBaseShippingInclTax($order);
                        break;
                    case 'grand_total':
                    case 'grand_total_incl':
                        $total['value'] = $this->orderHelper->getOperatorGrandTotalInclTax($order);
                        $total['base_value'] = $this->orderHelper->getOperatorBaseGrandTotalInclTax($order);
                        break;
                }
            }
        }

        return $totals;
    }
}
