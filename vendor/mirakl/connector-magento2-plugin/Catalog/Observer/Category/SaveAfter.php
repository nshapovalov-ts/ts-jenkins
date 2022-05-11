<?php
namespace Mirakl\Catalog\Observer\Category;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isEnabled()) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $observer->getEvent()->getCategory();

            if ($category->getData('mirakl_sync')) {
                $this->categoryHelper->update($category);
            } elseif ($category->getOrigData('mirakl_sync')) {
                $this->categoryHelper->delete($category);
            }
        }
    }
}
