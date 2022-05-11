<?php
namespace Mirakl\Adminhtml\Controller\Adminhtml\Payment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Mirakl\MMP\Common\Domain\Payment\PaymentStatus;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class Refuse extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$order = $this->_initOrder()) {
            return $resultRedirect->setUrl($this->getUrl('sales/order'));
        }

        try {
            $miraklOrder = $this->getMiraklOrder($order);
            if (!$miraklOrder) {
                throw new \Exception('Mirakl order not found.');
            }

            /** @var \Mirakl\Api\Helper\Payment $api */
            $api = $this->_objectManager->get('Mirakl\Api\Helper\Payment');

            // Call API
            $api->debitPayment($miraklOrder, $miraklOrder->getCustomer()->getId(), PaymentStatus::REFUSED);

            $this->messageManager->addSuccessMessage(__('The order debit has been refused successfully.'));
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = \Mirakl\parse_json_response($e->getResponse());
            $this->messageManager->addErrorMessage($response['message']);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e->getMessage());
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getUrl('sales/order/view', [
            'order_id' => $order->getId(),
            'active_tab' => 'mirakl'
        ]));

        return $resultRedirect;
    }

    /**
     * @param   OrderInterface   $order
     * @return  MiraklOrder|null
     */
    private function getMiraklOrder(OrderInterface $order)
    {
        $miraklOrder = null;
        if ($remoteId = $this->getRequest()->getParam('remote_id')) {
            /** @var \Mirakl\Connector\Helper\Order $connectorHelper */
            $connectorHelper = $this->_objectManager->get('Mirakl\Connector\Helper\Order');
            $miraklOrder = $connectorHelper->getMiraklOrderById($order->getIncrementId(), $remoteId);
        }

        return $miraklOrder;
    }
}