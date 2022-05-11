<?php
namespace Mirakl\FrontendDemo\Controller\Order;

use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;

class PostThread extends AbstractOrder
{
    /**
     * Submit new thread action
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
            try {
                $messageInput = [
                    'body' => $data['body'],
                    'to'   => $this->getTo($data['recipients']),
                ];

                if (!empty($data['subject'])) {
                    $messageInput['topic'] = [
                        'type'  => 'FREE_TEXT',
                        'value' => $data['subject'],
                    ];
                }

                $files = [];
                $fileData = $this->getRequest()->getFiles('file');
                if ($fileData && !empty($fileData['tmp_name'])) {
                    $file = new FileWrapper(new \SplFileObject($fileData['tmp_name']));
                    $file->setContentType($fileData['type']);
                    $file->setFileName($fileData['name']);
                    $files[] = $file;
                }

                $this->orderApi->createOrderThread($miraklOrder, new CreateOrderThread($messageInput), $files);

                $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));

                $this->session->setFormData([]);
            } catch (BadResponseException $e) {
                $response = \Mirakl\parse_json_response($e->getResponse());
                $message = $response['message'] ?? $e->getMessage();
                $this->session->setFormData($data);
                $this->logger->critical($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred while sending the message: %1', $message)
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

    /**
     * @param   string  $recipients
     * @return  array
     */
    protected function getTo($recipients)
    {
        $to = [];

        $addSeller = ($recipients === 'SHOP' || $recipients === 'BOTH');
        $addOperator = ($recipients === 'OPERATOR' || $recipients === 'BOTH');

        if ($addSeller) {
            $to[] = 'SHOP';
        }

        if ($addOperator) {
            $to[] = 'OPERATOR';
        }

        return $to;
    }
}
