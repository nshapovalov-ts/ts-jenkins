<?php
namespace Mirakl\Adminhtml\Block\Widget\Grid\Column\Renderer\MiraklOrder;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer;
use Magento\Framework\DataObject;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Mirakl\Connector\Helper\Config;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Common\Domain\Shipment\Shipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentLine;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentStatus;

class Shipments extends Renderer\Text
{
    /**
     * @var ShipmentApi
     */
    protected $shipmentApi;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param   Context     $context
     * @param   ShipmentApi $shipmentApi
     * @param   Config      $config
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        ShipmentApi $shipmentApi,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->shipmentApi = $shipmentApi;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function render(DataObject $row)
    {
        if (!$this->config->isEnableMultiShipments() || $row->getStatus()->getState() == OrderState::CANCELED) {
            return __('N/A');
        }

        $shipments = $this->shipmentApi->getShipments([$row->getId()]);

        $totalQty = $this->getTotalQty($row);
        $qtyShipped = 0;
        $qtyReceived = 0;

        foreach ($shipments->getCollection() as $shipment) {
            /** @var Shipment $shipment */
            foreach ($shipment->getShipmentLines() as $shipmentLine) {
                /** @var ShipmentLine $shipmentLine */
                if (in_array($shipment->getStatus(), $this->getShippedStatuses())) {
                    $qtyShipped += $shipmentLine->getQuantity();
                }
                if (in_array($shipment->getStatus(), $this->getReceivedStatuses())) {
                    $qtyReceived += $shipmentLine->getQuantity();
                }
            }
        }

        $html = __('%1 item(s) to ship', $totalQty - $qtyShipped);
        $html .= '<br>';
        $html .= __('%1 item(s) shipped', $qtyShipped);
        $html .= '<br>';
        $html .= __('%1 item(s) received', $qtyReceived);

        return $html;
    }

    /**
     * @return  array
     */
    protected function getReceivedStatuses()
    {
        return [
            ShipmentStatus::RECEIVED,
            ShipmentStatus::CLOSED,
        ];
    }

    /**
     * @return  array
     */
    protected function getShippedStatuses()
    {
        return [
            ShipmentStatus::SHIPPED,
            ShipmentStatus::TO_COLLECT,
            ShipmentStatus::RECEIVED,
            ShipmentStatus::CLOSED
        ];
    }

    /**
     * @param   DataObject  $row
     * @return  int
     */
    protected function getTotalQty(DataObject $row)
    {
        $qty = 0;
        /** @var \Mirakl\MMP\FrontOperator\Domain\Order\OrderLine $orderLine */
        foreach ($row->getOrderLines() as $orderLine) {
            if ($orderLine->getStatus()->getState() != 'REFUSED') {
                $qty += $orderLine->getQuantity();
            }
        }

        return $qty;
    }
}
