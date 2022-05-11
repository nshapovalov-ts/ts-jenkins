<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class LockOrderItemObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Item $item */
        $item = $observer->getEvent()->getItem();
        if ($item && $item->getMiraklOfferId()) {
            $item->setLockedDoInvoice(true);
            $item->setLockedDoShip(true);
        }
    }
}