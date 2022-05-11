<?php
namespace Mirakl\MMP\FrontOperator\Domain\Collection\Order\Update;

use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\FrontOperator\Domain\Order\Update\UpdatedOrderAndError;

/**
 * @method  UpdatedOrderAndError    current()
 * @method  UpdatedOrderAndError    first()
 * @method  UpdatedOrderAndError    get($offset)
 * @method  UpdatedOrderAndError    offsetGet($offset)
 * @method  UpdatedOrderAndError    last()
 */
class UpdatedOrderAndErrorCollection extends MiraklCollection
{
    /**
     * @var string
     */
    protected $itemClass = UpdatedOrderAndError::class;
}