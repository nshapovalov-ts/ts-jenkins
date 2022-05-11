<?php
namespace Mirakl\MCM\Front\Domain\Product\Export;

use Mirakl\MCM\Front\Domain\Collection\Product\ProductSourceCollection;
use Mirakl\MCM\Front\Domain\Collection\Product\ProductUrlCollection;
use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;
use Mirakl\MCM\FrontOperator\Domain\Product\Export\AbstractProductExport;

/**
 * @method  ProductAcceptance       getAcceptance()
 * @method  $this                   setAcceptance(ProductAcceptance $productAcceptance)
 * @method  ProductUrlCollection    getProductUrls()
 * @method  $this                   setProductUrls(ProductUrlCollection $productUrlCollection)
 * @method  ProductSourceCollection getSources()
 * @method  $this                   setSources(ProductSourceCollection $productSourceCollection)
 * @method  ProductSynchronization  getSynchronization()
 * @method  $this                   setSynchronization(ProductSynchronization $productValidation)
 * @method  ProductValidation       getValidation()
 * @method  $this                   setValidation(ProductValidation $productValidation)
 */
class ProductExport extends AbstractProductExport
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'acceptance'        => [ProductAcceptance::class, 'create'],
        'product_urls'      => [ProductUrlCollection::class, 'create'],
        'sources'           => [ProductSourceCollection::class, 'create'],
        'synchronization'   => [ProductSynchronization::class, 'create'],
        'validation'        => [ProductValidation::class, 'create'],
    ];
}