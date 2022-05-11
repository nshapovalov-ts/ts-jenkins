<?php
namespace Mirakl\MCM\Front\Domain\Product\Export;

use Mirakl\MCM\Front\Domain\Collection\Product\ProductIntegrationErrorCollection;
use Mirakl\MCM\FrontOperator\Domain\Product\Export\AbstractProductSynchronization;

/**
 * @method  ProductIntegrationErrorCollection   getIntegrationErrors()
 * @method  $this                               setIntegrationErrors(ProductIntegrationErrorCollection $integrationErrors)
 */
class ProductSynchronization extends AbstractProductSynchronization
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'integration_errors' => [ProductIntegrationErrorCollection::class, 'create'],
    ];
}