<?php
namespace Mirakl\FrontendDemo\Block\Order;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\MMP\Common\Domain\Shipment\Shipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentStatus;

class Shipments extends View
{
    /**
     * @var ShipmentApi
     */
    protected $shipmentApi;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var string
     */
    protected $_template = 'order/shipments.phtml';

    /**
     * @param   Context         $context
     * @param   Registry        $registry
     * @param   HttpContext     $httpContext
     * @param   OrderHelper     $orderHelper
     * @param   ShipmentApi     $shipmentApi
     * @param   ConnectorConfig $connectorConfig
     * @param   array           $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        HttpContext $httpContext,
        OrderHelper $orderHelper,
        ShipmentApi $shipmentApi,
        ConnectorConfig $connectorConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $httpContext, $orderHelper, $data);
        $this->shipmentApi = $shipmentApi;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * @param   Shipment    $shipment
     * @return  bool
     */
    public function canReceiveShipment(Shipment $shipment)
    {
        return in_array($shipment->getStatus(), [
            ShipmentStatus::SHIPPING,
            ShipmentStatus::SHIPPED,
            ShipmentStatus::TO_COLLECT,
        ]);
    }

    /**
     * @return  bool
     */
    public function isEnableMultiShipments()
    {
        return $this->connectorConfig->isEnableMultiShipments();
    }

    /**
     * @param   int $itemId
     * @return  \Magento\Framework\DataObject|\Magento\Sales\Model\Order\Item|null
     */
    public function getOrderItemById($itemId)
    {
        return $this->getOrder()->getItemsCollection()->getItemById($itemId);
    }

    /**
     * @param   int $limit
     * @return  \Mirakl\MMP\Common\Domain\Collection\SeekableCollection|null
     */
    public function getShipments($limit = 100)
    {
        try {
            $miraklOrder = $this->getMiraklOrder();
            $shipments = $this->shipmentApi->getShipments([$miraklOrder->getId()], [], $limit);
        } catch (\Exception $e) {
            return null;
        }

        return $shipments;
    }

    /**
     * @param   string  $code
     * @return  string
     */
    public function getStatusLabel($code)
    {
        return ucfirst(strtolower(str_replace('_', ' ', $code)));
    }
}
