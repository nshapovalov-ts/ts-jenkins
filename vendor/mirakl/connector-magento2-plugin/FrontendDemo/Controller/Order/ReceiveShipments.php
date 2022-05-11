<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Mirakl\Api\Helper\Shipment as ShipmentApi;
use Mirakl\MMP\Common\Domain\Shipment\Shipment;

class ReceiveShipments extends AbstractOrder
{
    /**
     * Verify that given shipment id is part of the Mirakl order (calls API ST11)
     *
     * @param string $miraklOrderId
     * @param string $shipmentId
     * @throws \InvalidArgumentException
     */
    protected function checkShipmentId($miraklOrderId, $shipmentId)
    {
        $shipments = $this->getShipmentApi()->getShipments([$miraklOrderId]);

        /** @var Shipment $shipment */
        foreach ($shipments->getCollection() as $shipment) {
            if ($shipment->getId() == $shipmentId) {
                return;
            }
        }

        throw new \InvalidArgumentException(
            __('Could not find shipment id %1 in order %2', $shipmentId, $miraklOrderId)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        $result = $this->initOrders();

        if ($result !== true) {
            return $result;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->registry->registry('current_order');
        /** @var \Mirakl\MMP\FrontOperator\Domain\Order $miraklOrder */
        $miraklOrder = $this->registry->registry('mirakl_order');

        $shipmentId = $this->getRequest()->getPostValue('shipment_id');

        try {
            $this->checkShipmentId($miraklOrder->getId(), $shipmentId);

            $result = $this->getShipmentApi()->receiveShipments([$shipmentId]);

            if ($result->getShipmentErrors()->count()) {
                throw new \Exception($result->getShipmentErrors()->first()->getMessage());
            }

            if ($result->getShipmentSuccess()->count()) {
                $this->messageManager->addSuccessMessage(__('Your shipment has been validated as received.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while validating your shipment.'));
            $this->logger->error($e->getMessage());
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/shipments', [
            'order_id' => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }

    /**
     * @return  ShipmentApi
     */
    protected function getShipmentApi()
    {
        return $this->_objectManager->get(ShipmentApi::class);
    }
}
