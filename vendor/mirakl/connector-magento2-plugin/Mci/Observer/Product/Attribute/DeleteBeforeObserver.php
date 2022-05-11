<?php
namespace Mirakl\Mci\Observer\Product\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteBeforeObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isApiEnabled() && $this->mciConfigHelper->isSyncValuesLists()) {
            /** @var EavAttribute $attribute */
            $attribute = $observer->getEvent()->getAttribute();
            if ($this->valueListHelper->isAttributeExportable($attribute)) {
                $this->valueListHelper->delete($attribute);
            }
        }
    }
}