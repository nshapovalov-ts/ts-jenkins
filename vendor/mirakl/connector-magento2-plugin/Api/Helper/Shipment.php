<?php
namespace Mirakl\Api\Helper;

use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Collection\Shipment\ShipmentIdentifierCollection;
use Mirakl\MMP\Common\Domain\Shipment\Workflow\ShipmentWorkflowResponse;
use Mirakl\MMP\FrontOperator\Request\Shipment\GetShipmentsRequest;
use Mirakl\MMP\FrontOperator\Request\Shipment\ReceiveShipmentsRequest;

class Shipment extends ClientHelper\MMP
{
    /**
     * (ST11) List shipments of given orders
     *
     * @param   array   $orderIds
     * @param   array   $stateCodes     @see \Mirakl\MMP\Common\Domain\Shipment\ShipmentStatus
     * @param   int     $limit
     * @return  SeekableCollection
     */
    public function getShipments(array $orderIds = [], array $stateCodes = [], $limit = 10)
    {
        $request = new GetShipmentsRequest();

        if (!empty($orderIds)) {
            $request->setOrderIds($orderIds);
        }

        if (!empty($stateCodes)) {
            $request->setShipmentStateCodes($stateCodes);
        }

        // Force limit in range 1-100
        $limit = max(1, min(100, abs((int) $limit)));
        $request->setLimit($limit);

        return $this->send($request);
    }

    /**
     * (ST11) List shipments according to given page token
     *
     * @param   string  $pageToken
     * @return  SeekableCollection
     */
    public function getShipmentsPage($pageToken)
    {
        $request = new GetShipmentsRequest();
        $request->setPageToken($pageToken);

        return $this->send($request);
    }

    /**
     * (ST25) Validate shipments as received
     *
     * @param   array   $shipmentIds
     * @return  ShipmentWorkflowResponse
     */
    public function receiveShipments(array $shipmentIds)
    {
        $shipments = new ShipmentIdentifierCollection();
        foreach ($shipmentIds as $shipmentId) {
            $shipments->add(['id' => $shipmentId]);
        }

        $request = new ReceiveShipmentsRequest($shipments);

        return $this->send($request);
    }
}
