<?php
namespace Mirakl\Catalog\Observer\Category;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateTreeSyncFlagObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isEnabled()) {
            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
            $collection = $observer->getEvent()->getCategories();
            $action = $observer->getEvent()->getAction() ? 'update' : 'delete';

            $this->categoryHelper->exportCollection($collection, $action);
        }
    }
}