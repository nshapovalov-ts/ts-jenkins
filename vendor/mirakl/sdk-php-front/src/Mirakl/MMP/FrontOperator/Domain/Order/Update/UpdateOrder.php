<?php
namespace Mirakl\MMP\FrontOperator\Domain\Order\Update;

use Mirakl\Core\Domain\MiraklObject;

/**
 * @method  UpdateOrderCustomer     getCustomer()
 * @method  $this                   setCustomer(UpdateOrderCustomer $updatedOrders)
 * @method  string                  getOrderId();
 * @method  $this                   setOrderId(string $orderId)
 * @method  string                  getPaymentWorkflow();
 * @method  $this                   setPaymentWorkflow(string $paymentWorkflow)
 * @method  UpdateShopStatistics    getShopStatistics();
 * @method  $this                   setShopStatistics(UpdateShopStatistics $shopStatistics)
 * @method  \DateTime               getShippingDeadline();
 * @method  $this                   setShippingDeadline(\DateTime $shippingDeadline)
 */
class UpdateOrder extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'customer' => [UpdateOrderCustomer::class, 'create'],
    ];
}