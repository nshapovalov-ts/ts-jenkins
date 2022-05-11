<?php
namespace Mirakl\MMP\FrontOperator\Domain\Order\Update;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\Common\Domain\Collection\ErrorCollection;

/**
 * @method  ErrorCollection getErrors()
 * @method  $this           setErrors(ErrorCollection|array $errors)
 * @method  string          getOrderId();
 * @method  $this           setOrderId(string $orderId)
 * @method  UpdatedOrder    getUpdated();
 * @method  $this           setUpdated(UpdatedOrder $updated)
 */
class UpdatedOrderAndError extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'errors'  => [ErrorCollection::class, 'create'],
        'updated' => [UpdatedOrder::class, 'create'],
    ];
}