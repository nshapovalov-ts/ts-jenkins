<?php
namespace Mirakl\Mci\Observer\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveAfterObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isEnabled()) {
            /** @var \Magento\Catalog\Model\Category $category */
            $category = $observer->getEvent()->getCategory();
            if ($this->canUpdate($category)) {
                $this->hierarchyHelper->update($category);
            }
        }
    }

    /**
     * @param   Category    $category
     * @return  bool
     */
    protected function canUpdate(Category $category)
    {
        return in_array($this->getRootCategoryId(), $category->getParentIds())
            && $category->getStoreId() == 0
            || $this->mciConfigHelper->isStoreInLabelTranslation($category->getStore())
            || $this->mciConfigHelper->getCatalogIntegrationStore()->getId() == $category->getStoreId();
    }
}
