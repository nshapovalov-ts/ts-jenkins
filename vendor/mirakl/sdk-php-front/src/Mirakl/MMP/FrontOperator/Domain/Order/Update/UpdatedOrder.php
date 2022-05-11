<?php
namespace Mirakl\MMP\FrontOperator\Domain\Order\Update;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\Common\Domain\Order\DeliveryDate;

/**
 * @method  UpdatedOrderCustomer    getCustomer()
 * @method  $this                   setCustomer(UpdatedOrderCustomer $customer)
 * @method  DeliveryDate            getDeliveryDate()
 * @method  $this                   setDeliveryDate(DeliveryDate $deliveryDate)
 * @method  int                     getLeadtimeToShip()
 * @method  $this                   setLeadtimeToShip(int $leadtimeToShip)
 * @method  string                  getPaymentWorkflow()
 * @method  $this                   setPaymentWorkflow(string $paymentWorkflow)
 * @method  UpdatedShopStatistics   getShopStatistics()
 * @method  $this                   setShopStatistics(UpdatedShopStatistics $shopStatistics)
 * @method  \DateTime               getShippingDeadline()
 * @method  $this                   setShippingDeadline(\DateTime $shippingDeadline)
 */
class UpdatedOrder extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'customer'         => [UpdatedOrderCustomer::class, 'create'],
        'delivery_date'    => [DeliveryDate::class, 'create'],
        'shop_statistics'  => [UpdatedShopStatistics::class, 'create'],
    ];
}