<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class PostIncident extends AbstractOrder
{
    /**
     * Submit incident action
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

        $type = $this->_request->getParam('type');
        if (!in_array($type, ['close', 'open'])) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $data = $this->getRequest()->getPostValue();
        if (!empty($data)) {
            $orderLineId = $data['order_line'];
            $reason = $data['reason'];

            try {
                if ($type == 'open') {
                    $this->orderApi->openIncident($miraklOrder, $orderLineId, $reason);
                    $this->messageManager->addSuccessMessage(
                        __('Incident has been successfully created.')
                    );
                } else {
                    $this->orderApi->closeIncident($miraklOrder, $orderLineId, $reason);
                    $this->messageManager->addSuccessMessage(
                        __('Incident has been successfully closed.')
                    );
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
                $this->messageManager->addErrorMessage(
                    $type == 'open'
                    ? __('An error occurred while opening an incident.')
                    : __('An error occurred while closing incident.')
                );
            }
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/view', [
            'order_id' => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }
}