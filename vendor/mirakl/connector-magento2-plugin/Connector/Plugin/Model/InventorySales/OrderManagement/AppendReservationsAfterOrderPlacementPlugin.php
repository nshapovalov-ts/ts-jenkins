<?php
namespace Mirakl\Connector\Plugin\Model\InventorySales\OrderManagement;

use Magento\InventorySales\Plugin\Sales\OrderManagement\AppendReservationsAfterOrderPlacementPlugin as Plugin;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

class AppendReservationsAfterOrderPlacementPlugin
{
    /**
     * This is a plugin on a plugin implemented since Magento 2.3.0.
     * The default plugin handles stock reservations after an order is placed
     * but we have to exclude marketplace order items from it.
     *
     * @see AppendReservationsAfterOrderPlacementPlugin::afterPlace
     *
     * @param   Plugin                      $plugin
     * @param   \Closure                    $proceed
     * @param   OrderManagementInterface    $orderManagement
     * @param   OrderInterface              $order
     * @return  OrderInterface
     */
    public function aroundAfterPlace(
        Plugin $plugin,
        \Closure $proceed,
        OrderManagementInterface $orderManagement,
        OrderInterface $order
    ) {
        $items = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getItems() as $item) {
            $parentItem = $item->getParentItem();
            if (!$item->getMiraklOfferId() && (!$parentItem || !$parentItem->getMiraklOfferId())) {
                $items[] = $item; // Handle item only if it's not a marketplace item
            }
        }

        if (empty($items)) {
            return $order; // Do not call the default plugin if no operator item is present
        }

        $modifiedOrder = clone $order; // Do not interfere with the default order object
        $modifiedOrder->setItems($items);

        return $proceed($orderManagement, $modifiedOrder);
    }
}