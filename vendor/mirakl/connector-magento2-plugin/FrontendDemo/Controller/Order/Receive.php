<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Receive extends AbstractOrder
{
    /**
     *  Mark a Mirakl order as RECEIVED
     *
     * @return  ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $result = $this->initOrders();
        if ($result !== true) {
            return $result;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->registry->registry('current_order');

        /** @var \Mirakl\MMP\FrontOperator\Domain\Order $miraklOrder */
        $miraklOrder = $this->registry->registry('mirakl_order');

        try {
            $this->orderApi->receiveOrder($miraklOrder);
            $this->messageManager->addSuccessMessage(
                __('Your order has been marked as received successfully.')
            );
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while marking order as received.')
            );
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());

            return $resultRedirect;
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/evaluation', [
            'order_id' => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }
}
