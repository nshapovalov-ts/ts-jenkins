<?php
namespace Mirakl\MCM\Front\Domain\Collection\Product;

use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MCM\Front\Domain\Product\Export\ProductDeleted;

/**
 * @method ProductDeleted current()
 * @method ProductDeleted first()
 * @method ProductDeleted get($offset)
 * @method ProductDeleted offsetGet($offset)
 * @method ProductDeleted last()
 */
class ProductDeletedCollection extends MiraklCollection
{
    /**
     * @var string
     */
    protected $itemClass = ProductDeleted::class;
}