<?php
namespace Mirakl\MMP\FrontOperator\Domain\Collection\Order\Update;

use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\FrontOperator\Domain\Order\Update\UpdateOrder;

/**
 * @method  UpdateOrder current()
 * @method  UpdateOrder first()
 * @method  UpdateOrder get($offset)
 * @method  UpdateOrder offsetGet($offset)
 * @method  UpdateOrder last()
 */
class UpdateOrderCollection extends MiraklCollection
{
    /**
     * @var string
     */
    protected $itemClass = UpdateOrder::class;
}