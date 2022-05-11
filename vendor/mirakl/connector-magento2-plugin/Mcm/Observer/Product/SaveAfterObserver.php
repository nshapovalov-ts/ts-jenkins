<?php
namespace Mirakl\Mcm\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mirakl\Mcm\Helper\Data as McmHelper;

class SaveAfterObserver extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if (!$this->isEnabled()) {
            return;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getData('product');

        if ($product->getData('mirakl_sync') && $product->getData(McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER)) {
            // Update product on Mirakl platform
            $this->processHelper->exportProduct($product->getId());
        }
    }
}
