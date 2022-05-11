<?php
namespace Mirakl\Catalog\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteBefore extends AbstractObserver implements ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->isEnabled()) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();

            if ($product->getOrigData('mirakl_sync')) {
                $this->productHelper->delete($product);
            }
        }
    }
}
