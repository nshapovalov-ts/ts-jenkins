<?php

namespace Mirakl\Catalog\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Catalog\Model\Product\Attribute\MassUpdate;

class MassUpdateAttributesAfter extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var MassUpdate $massUpdate */
        $massUpdate = $this->registry->registry('mass_update_mirakl_p21_collection');

        if (!$this->isEnabled() || empty($massUpdate) || empty($massUpdate->getCollection()) || !$massUpdate->getCollection()->count()) {
            return;
        }

        $this->productHelper->exportCollection($massUpdate->getCollection(), $massUpdate->getAction());

        $this->registry->unregister('mass_update_mirakl_p21_collection');
    }
}