<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Shipment;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ValidateShipmentsReceiptResolver extends AbstractShipmentResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->checkLoggedCustomer($context);

        $shipmentIds = $this->getInput($args, 'mp_shipment_ids', true);

        try {
            $shipmentWorkflowResponse = $this->shipmentHelper->receiveShipments($shipmentIds);

            return $shipmentWorkflowResponse->toArray();
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }
    }
}
