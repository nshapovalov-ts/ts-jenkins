<?php
namespace Mirakl\MMP\FrontOperator\Domain\Order\Update;

use Mirakl\Core\Domain\MiraklObject;

/**
 * @method  UpdateCustomerBillingAddress    getBillingAddress()
 * @method  $this                           setBillingAddress(UpdateCustomerBillingAddress $billingAddress)
 */
class UpdateOrderCustomer extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'billing_address' => [UpdateCustomerBillingAddress::class, 'create'],
    ];
}