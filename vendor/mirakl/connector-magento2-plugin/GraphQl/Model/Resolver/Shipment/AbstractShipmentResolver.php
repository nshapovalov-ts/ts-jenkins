<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Shipment;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Mirakl\Api\Helper\Shipment as ShipmentHelper;
use Mirakl\GraphQl\Model\Resolver\AbstractResolver;

abstract class AbstractShipmentResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;

    /**
     * @param  ShipmentHelper   $shipmentHelper
     */
    public function __construct(ShipmentHelper $shipmentHelper)
    {
        $this->shipmentHelper = $shipmentHelper;
    }
}
