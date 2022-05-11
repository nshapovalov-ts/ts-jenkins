<?php
namespace Mirakl\MCM\Front\Domain\Collection\Product;

use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductIntegrationError;
use Mirakl\MCM\FrontOperator\Domain\Collection\Product\AbstractProductIntegrationErrorCollection;

/**
 * @method ProductIntegrationError current()
 * @method ProductIntegrationError first()
 * @method ProductIntegrationError get($offset)
 * @method ProductIntegrationError offsetGet($offset)
 * @method ProductIntegrationError last()
 */
class ProductIntegrationErrorCollection extends AbstractProductIntegrationErrorCollection
{
    /**
     * @var string
     */
    protected $itemClass = ProductIntegrationError::class;
}