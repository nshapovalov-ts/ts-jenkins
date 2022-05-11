<?php
namespace Mirakl\FrontendDemo\Block\Product\Offer;

use Magento\Catalog\Model\Product;
use Mirakl\Connector\Model\Offer;

/**
 * @method  Offer   getOffer
 * @method  $this   setOffer(Offer $offer)
 * @method  Product getProduct
 * @method  $this   setProduct(Product $product)
 */
class PriceRanges extends Price
{
    /**
     * @var string
     */
    protected $_template = 'product/offer/price_ranges.phtml';
}
