<?php
namespace Mirakl\Catalog\Observer\Product;

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
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();

            if ($product->getData('mirakl_sync')) {
                if ($product->getOrigData('sku') && $product->getSku() != $product->getOrigData('sku')) {
                    // Delete old product (because sku changed) if it exists in Mirakl platform
                    $old = clone $product;
                    $old->setSku($product->getOrigData('sku'));
                    $this->productHelper->delete($old);
                }
                // Update product on Mirakl platform
                $this->productHelper->update($product);
            } elseif ($product->getOrigData('mirakl_sync')) {
                // Delete product if it's not synchronized with Mirakl anymore
                $this->productHelper->delete($product);
            }
        }
    }
}
