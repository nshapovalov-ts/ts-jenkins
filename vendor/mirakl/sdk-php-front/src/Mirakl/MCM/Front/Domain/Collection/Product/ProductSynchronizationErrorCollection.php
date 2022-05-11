<?php
namespace Mirakl\MCM\Front\Domain\Collection\Product;

use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductSynchronizationError;
use Mirakl\MCM\FrontOperator\Domain\Collection\Product\AbstractProductIntegrationErrorCollection;

/**
 * @method ProductSynchronizationError current()
 * @method ProductSynchronizationError first()
 * @method ProductSynchronizationError get($offset)
 * @method ProductSynchronizationError offsetGet($offset)
 * @method ProductSynchronizationError last()
 */
class ProductSynchronizationErrorCollection extends AbstractProductIntegrationErrorCollection
{
    /**
     * @var string
     */
    protected $itemClass = ProductSynchronizationError::class;
}