<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderMessage;

class PostMessage extends AbstractOrder
{
    /**
     * Submit new message action
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

        $data = $this->session->getFormData(true);
        if (!$data) {
            $data = $this->getRequest()->getPostValue();
        }

        if (!empty($data)) {
            $subject = $data['subject'];
            $body = $data['body'];

            try {
                $customer = $this->customerSession->getCustomerDataObject();
                $message = new CreateOrderMessage();
                $message
                    ->setCustomerId($order->getCustomerId() ?: $customer->getId())
                    ->setCustomerFirstname($order->getCustomerFirstname() ?: $customer->getFirstname())
                    ->setCustomerLastname($order->getCustomerLastname() ?: $customer->getLastname())
                    ->setCustomerEmail($order->getCustomerEmail() ?: $customer->getEmail())
                    ->setToShop(true)
                    ->setSubject($subject)
                    ->setBody($body);

                $this->orderApi->createOrderMessage($miraklOrder, $message);

                $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));
            } catch (BadResponseException $e) {
                $response = \Mirakl\parse_json_response($e->getResponse());
                $this->session->setFormData($data);
                $this->logger->critical($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred while sending the message: %1', $response['message'])
                );
            } catch (\Exception $e) {
                $this->session->setFormData($data);
                $this->logger->warning($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred while sending the message: %1', $e->getMessage())
                );
            }
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/message', [
            'order_id' => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }
}
