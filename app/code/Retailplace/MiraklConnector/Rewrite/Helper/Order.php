<?php
namespace Retailplace\MiraklConnector\Rewrite\Helper;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order as OrderModel;
use Mirakl\Connector\Helper\Order as HelperOrder;
use Magento\Framework\App\Area;

class Order extends HelperOrder
{
    private $labels;

    /**
     * Returns shipping description of specified order including Mirakl order items
     *
     * @param OrderModel $order
     * @return  string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShippingDescription(OrderModel $order)
    {
        $labels = [];

        if ($this->labels !== null) {
            return $this->labels;
        }

        if (!$order->getId()) {
            return $order->getData(OrderInterface::SHIPPING_DESCRIPTION);
        }

        if (!$order->getMiraklSent() || $this->isVisible()) {
            foreach ($order->getAllItems() as $item) {
                if ($item->getMiraklShopId()) {
                    $labels[] = $item->getMiraklShippingTypeLabel();
                }
            }
        }

        if (!$this->isFullMiraklOrder($order)) {
            array_unshift($labels, $order->getData(OrderInterface::SHIPPING_DESCRIPTION));
        }

        $this->labels = implode(', ', array_unique(array_filter($labels)));

        return $this->labels;
    }

    /**
     * Is Visible
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isVisible()
    {
        return in_array($this->appState->getAreaCode(), [Area::AREA_ADMINHTML, Area::AREA_FRONTEND]);
    }
}
