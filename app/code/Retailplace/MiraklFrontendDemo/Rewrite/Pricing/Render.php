<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Pricing;

use Magento\Catalog\Model\Product;

class Render extends \Mirakl\FrontendDemo\Pricing\Render
{
    /**
     * Returns saleable item instance
     *
     * @return Product
     */
    protected function getProduct()
    {
        $product = \Magento\Catalog\Pricing\Render::getProduct();

        $operatorOffer = $this->offerHelper->getBestOperatorOffer($product);
        if ($operatorOffer) {
            return $product;
        }

        $offer = $this->offerHelper->getBestOffer($product);
        if (!$offer) {
            return $product;
        }

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
        $renderProduct->setData('main_offer', $offer);
        $renderProduct->setData('product', $product);

        return $renderProduct;
    }
}
