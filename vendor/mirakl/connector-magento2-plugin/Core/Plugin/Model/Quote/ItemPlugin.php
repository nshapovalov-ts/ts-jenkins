<?php
namespace Mirakl\Core\Plugin\Model\Quote;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;

class ItemPlugin
{
    /**
     * @param   Item        $item
     * @param   \Closure    $proceed
     * @return  Product
     */
    public function aroundGetProduct(Item $item, \Closure $proceed)
    {
        $product = $proceed();

        if ($item->getMiraklCustomTaxApplied()) {
            // Remove product tax class from any quote item having Mirakl custom taxes applied.
            // The product object is the same if it is present several times in the cart (singleton). So we need
            // to clone it in order to not interfere with the potential other product lines.
            $product = clone $product;
            $product->unsTaxClassId();
        }

        return $product;
    }
}