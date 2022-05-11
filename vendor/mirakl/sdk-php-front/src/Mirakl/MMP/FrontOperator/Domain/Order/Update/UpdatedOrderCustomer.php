<?php
namespace Mirakl\MMP\FrontOperator\Domain\Order\Update;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\Common\Domain\Order\CustomerBillingAddress;

/**
 * @method  CustomerBillingAddress  getBillingAddress()
 * @method  $this                   setBillingAddress(CustomerBillingAddress $billingAddress)
 */
class UpdatedOrderCustomer extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'billing_address' => [CustomerBillingAddress::class, 'create'],
    ];
}