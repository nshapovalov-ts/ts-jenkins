<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetQuoteItemFreeShippingObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $item */
        $item = $observer->getEvent()->getItem();
        if ($item && $item->getMiraklOfferId()) {
            $item->setFreeShipping(true);
        }
    }
}