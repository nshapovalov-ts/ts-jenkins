<?php
namespace Mirakl\Adminhtml\Controller\Adminhtml\Order;

class Send extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Sales\Model\Order $order */
        if ($order = $this->_initOrder()) {
            try {
                /** @var \Mirakl\Connector\Helper\Order $orderHelper */
                $orderHelper = $this->_objectManager->get('Mirakl\Connector\Helper\Order');

                $createdOrders = $orderHelper->createMiraklOrder($order);
                $offersNotShippable = $createdOrders->getOffersNotShippable();
                if ($offersNotShippable && $offersNotShippable->count()) {
                    $reason = $offersNotShippable->first()->getReason();
                    throw new \Exception(__('Something went wrong while sending the order to Mirakl: %1.', $reason));
                }

                $this->messageManager->addSuccessMessage(__('The order has been sent to Mirakl.'));
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                $response = \Mirakl\parse_json_response($e->getResponse());
                $this->messageManager->addErrorMessage($response['message']);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->error($e->getMessage());
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['_current' => true]);

        return $resultRedirect;
    }
}