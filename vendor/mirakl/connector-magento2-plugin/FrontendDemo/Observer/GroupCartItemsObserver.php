<?php
namespace Mirakl\FrontendDemo\Observer;

use Magento\Framework\Event\Observer;

class GroupCartItemsObserver extends AbstractObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->apiConfig->isEnabled()) {
            $block = $observer->getEvent()->getBlock();
            if ($block instanceof \Magento\Checkout\Block\Cart) {
                /**
                 * Setting custom items grouped by orders in order to display shipping methods
                 * @see \Magento\Checkout\Block\Cart::getItems()
                 */
                $items = $this->quoteHelper->getGroupedItems();
                $block->setCustomItems($items);
            }
        }
    }
}