<?php
namespace Mirakl\Connector\Plugin\Observer\CatalogInventory;

use Magento\CatalogInventory\Observer\CancelOrderItemObserver;
use Magento\Framework\Event\Observer as EventObserver;

class CancelOrderItemObserverPlugin
{
    /**
     * Do not increment product qty if order item is cancelled and is a marketplace offer
     *
     * @param   CancelOrderItemObserver $subject
     * @param   \Closure                $proceed
     * @param   EventObserver           $observer
     * @return  void
     */
    public function aroundExecute(CancelOrderItemObserver $subject, \Closure $proceed, EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        $item = $observer->getEvent()->getItem();

        if (!$item->getMiraklOfferId()) {
            $proceed($observer);
        }
    }
}
