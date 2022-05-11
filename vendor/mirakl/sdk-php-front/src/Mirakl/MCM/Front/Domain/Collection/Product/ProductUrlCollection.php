<?php
namespace Mirakl\MCM\Front\Domain\Collection\Product;

use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MCM\Front\Domain\Product\Export\ProductUrl;

/**
 * @method ProductUrl current()
 * @method ProductUrl first()
 * @method ProductUrl get($offset)
 * @method ProductUrl offsetGet($offset)
 * @method ProductUrl last()
 */
class ProductUrlCollection extends MiraklCollection
{
    /**
     * @var string
     */
    protected $itemClass = ProductUrl::class;
}
