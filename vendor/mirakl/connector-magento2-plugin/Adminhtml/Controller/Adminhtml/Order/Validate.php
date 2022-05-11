<?php
namespace Mirakl\Adminhtml\Controller\Adminhtml\Order;

use Magento\Framework\Controller\ResultFactory;

class Validate extends \Magento\Sales\Controller\Adminhtml\Order
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
            /** @var \Mirakl\Api\Helper\Order $api */
            $api = $this->_objectManager->get('Mirakl\Api\Helper\Order');

            // Call API
            $api->validateOrder($order->getIncrementId());

            $this->messageManager->addSuccessMessage(__('The order has been validated successfully.'));
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
}