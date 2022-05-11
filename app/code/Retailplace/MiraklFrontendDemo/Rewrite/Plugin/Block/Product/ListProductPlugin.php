<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Plugin\Block\Product;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class ListProductPlugin extends \Mirakl\FrontendDemo\Plugin\Block\Product\ListProductPlugin
{
    /**
     * @param   ListProduct $subject
     * @param   \Closure    $proceed
     * @param   Product     $product
     * @return  AbstractCollection
     */
    public function aroundGetProductPrice(ListProduct $subject, \Closure $proceed, Product $product)
    {
        $renderProduct = $product;

        if ($offer = $this->offerHelper->getBestOffer($product)) {
            $renderProduct = $this->productFactory->create();
            $renderProduct->setId($product->getId());
            $renderProduct->setSku($offer->getProductSku());
            $renderProduct->setPrice($offer->getPrice());
            $renderProduct->setQty($offer->getQuantity());
            $renderProduct->setTaxClassId($product->getTaxClassId());
            if ($offer->getOriginPrice() > $offer->getPrice()) {
                $renderProduct->setSpecialPrice($offer->getPrice());
                $renderProduct->setPrice($offer->getOriginPrice());
            }
            $renderProduct->setData('retail_price', $product->getRetailPrice());
            $renderProduct->setData('main_offer', $offer);
            $renderProduct->setData('product', $product);
        }

        return $proceed($renderProduct);
    }
}
