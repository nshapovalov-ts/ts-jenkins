<?php
namespace Mirakl\MMP\Front\Domain\Quote\Order;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\Common\Domain\Collection\AdditionalFieldValueCollection;
use Mirakl\MMP\Common\Domain\Order\DeliveryDate;

/**
 * @method  DeliveryDate                    getDeliveryDate()
 * @method  $this                           setDeliveryDate(DeliveryDate $deliveryDate)
 * @method  int                             getLeadtimeToShip()
 * @method  $this                           setLeadtimeToShip(int $leadtimeToShip)
 * @method  AdditionalFieldValueCollection  getOrderLineAdditionalFields()
 * @method  $this                           setOrderLineAdditionalFields(array|AdditionalFieldValueCollection $additionalFields)
 * @method  string                          getOrderLineId()
 * @method  $this                           setOrderLineId(string $orderLineId)
 * @method  string                          getQuoteLineId()
 * @method  $this                           setQuoteLineId(string $quoteLineId)
 * @method  \DateTime                       getShippingDeadline()
 * @method  $this                           setShippingDeadline(\DateTime $shippingDeadline)
 * @method  string                          getShippingPudoId()
 * @method  $this                           setShippingPudoId(string $shippingPudoId)
 */
class QuoteOrderLine extends MiraklObject
{
    /**
     * @var array
     */
    protected static $dataTypes = [
        'delivery_date'                => [DeliveryDate::class, 'create'],
        'order_line_additional_fields' => [AdditionalFieldValueCollection::class, 'create'],
    ];
}