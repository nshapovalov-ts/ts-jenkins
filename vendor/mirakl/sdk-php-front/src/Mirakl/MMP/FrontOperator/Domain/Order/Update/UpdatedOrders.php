<?php
namespace Mirakl\MMP\FrontOperator\Domain\Order\Update;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\FrontOperator\Domain\Collection\Order\Update\UpdatedOrderAndErrorCollection;

/**
 * @method  UpdatedOrderAndErrorCollection  getUpdatedOrders()
 * @method  $this                           setUpdatedOrders(UpdatedOrderAndErrorCollection $updatedOrders)
 */
class UpdatedOrders extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'updated_orders' => [UpdatedOrderAndErrorCollection::class, 'create'],
    ];
}