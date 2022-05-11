<?php
namespace Mirakl\Mci\Observer\Product\Attribute;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteAfterObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isApiEnabled() && $this->mciConfigHelper->isSyncAttributes()) {
            $this->attributeHelper->deleteAttribute();
        }
    }
}