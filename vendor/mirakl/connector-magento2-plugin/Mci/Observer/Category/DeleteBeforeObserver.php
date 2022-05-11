<?php
namespace Mirakl\Mci\Observer\Category;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteBeforeObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isEnabled()) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $observer->getEvent()->getCategory();
            if (in_array($this->getRootCategoryId(), $category->getParentIds())) {
                $this->hierarchyHelper->delete($category);
            }
        }
    }
}
