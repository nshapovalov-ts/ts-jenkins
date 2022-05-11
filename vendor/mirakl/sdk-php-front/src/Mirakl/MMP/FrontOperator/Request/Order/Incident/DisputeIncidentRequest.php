<?php
namespace Mirakl\MMP\FrontOperator\Request\Order\Incident;

use Mirakl\Core\Request\AbstractRequest;

/**
 * (OR65) Dispute incident
 *
 * @method  string          getOrderId()
 * @method  $this           setOrderId(string $orderId)
 * @method  string          getOrderLineId()
 * @method  $this           setOrderLineId(string $orderId)
 *
 * Example:
 *
 * <code>
 * use Mirakl\MMP\Front\Client\FrontApiClient;;
 * use Mirakl\MMP\FrontOperator\Request\Order\Incident\DisputeIncidentRequest;
 *
 * $api = new FrontApiClient('API_URL', 'API_KEY');
 * $request = new DisputeIncidentRequest('ORDER_ID', 'LINE');
 * $api->disputeIncident($request);
 * </code>
 */
class DisputeIncidentRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $method = 'PUT';

    /**
     * @var string
     */
    protected $endpoint = '/orders/{order}/lines/{line}/dispute_incident';

    /**
     * @var array
     */
    protected $uriVars = [
        '{order}' => 'order_id',
        '{line}' => 'order_line_id',
    ];

    /**
     * @param   string  $orderId
     * @param   String  $orderLineId
     */
    public function __construct($orderId, $orderLineId)
    {
        parent::__construct();
        $this->setOrderId($orderId);
        $this->setOrderLineId($orderLineId);
    }
}
