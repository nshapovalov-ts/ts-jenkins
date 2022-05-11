<?php
namespace Mirakl\MMP\FrontOperator\Domain\Product\Offer;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\FrontOperator\Domain\Collection\Product\Offer\OfferOnProductCollection;
use Mirakl\MMP\Common\Domain\Offer\ProductInfoWithRefs;

/**
 * @method  OfferOnProductCollection    getOffers()
 * @method  $this                       setOffers(array|OfferOnProductCollection $offers)
 * @method  ProductInfoWithRefs         getProduct()
 * @method  $this                       setProduct(array|ProductInfoWithRefs $productInfoWithRefs)
 * @method  int                         getTotalCount()
 * @method  int                         setTotalCount(int $totalCount)
 */
class ProductWithOffers extends MiraklObject
{
    /**
     * @var array
     */
    protected static $mapping = [
        'product_brand'       => 'product/brand',
        'product_description' => 'product/description',
        'product_media'       => 'product/media',
        'product_sku'         => 'product/sku',
        'product_title'       => 'product/title',
        'product_references'  => 'product/references',
        'category_code'       => 'product/category/code',
        'category_label'      => 'product/category/label',
        'category_type'       => 'product/category/type',
        'measurement'         => 'product/measurement',
    ];

    /**
     * @var array
     */
    protected static $dataTypes = [
        'offers'  => [OfferOnProductCollection::class, 'create'],
        'product' => [ProductInfoWithRefs::class, 'create'],
    ];
}