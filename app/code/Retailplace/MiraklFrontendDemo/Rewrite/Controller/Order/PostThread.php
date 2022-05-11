<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Controller\Order;

use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;
use Mirakl\FrontendDemo\Controller\Order\PostThread as OrderPostThread;
use Retailplace\MiraklFrontendDemo\Rewrite\Controller\Message\PostReply;
use SplFileObject;
use Exception;
use function Mirakl\parse_json_response;
use Mirakl\MMP\FrontOperator\Domain\Order;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class PostThread
 */
class PostThread extends OrderPostThread
{
    /**
     * Submit new thread action
     *
     * @return  ResultInterface
     */
    public function execute()
    {

        $errorMessages = [];

        /** @var Redirect $resultRedirect */
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
        /** @var Order $miraklOrder */
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
                $filesData = $this->getRequest()->getFiles('file');
                if ($filesData) {
                    foreach ($filesData as $fileData) {
                        if (!empty($fileData['tmp_name'])) {
                            $fileSize = $fileData['size'];
                            $fileName = $fileData['name'];
                            $fileType = $fileData['type'];

                            //validation
                            if ($fileSize) {
                                $fileSize = round($fileSize / (1024 * 1024), 0, PHP_ROUND_HALF_DOWN);
                                if ($fileSize > PostReply::FILE_MAX_SIZE) {
                                    $errorMessages[] = __(
                                        "The file was too big and couldn't be uploaded. "
                                        . "Use a file smaller than %1 MBs and try to upload again.",
                                        (int) PostReply::FILE_MAX_SIZE
                                    );
                                }
                            }

                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

                            if (!in_array(strtolower($fileExtension), PostReply::SUPPORT_FORMATS)) {
                                $errorMessages[] = __('Disallowed file %1 type.', $fileName);
                            }

                            $file = new FileWrapper(new SplFileObject($fileData['tmp_name']));
                            $file->setContentType($fileType);
                            $file->setFileName($fileName);
                            $files[] = $file;
                        }
                    }
                }

                if (!empty($errorMessages)) {
                    foreach ($errorMessages as $errorMessage) {
                        $this->logger->warning($errorMessage);
                        $this->messageManager->addErrorMessage($errorMessage);
                    }
                    return $resultRedirect->setUrl($this->url->getUrl('marketplace/order/message', [
                        'order_id'  => $order->getId(),
                        'remote_id' => $miraklOrder->getId(),
                    ]));
                }

                $this->orderApi->createOrderThread($miraklOrder, new CreateOrderThread($messageInput), $files);

                $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));

                $this->session->setFormData([]);
            } catch (BadResponseException $e) {
                $response = parse_json_response($e->getResponse());
                $message = $response['message'] ?? $e->getMessage();
                $this->session->setFormData($data);
                $this->logger->critical($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred while sending the message: %1', $message)
                );
            } catch (Exception $e) {
                $this->session->setFormData($data);
                $this->logger->warning($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('An error occurred while sending the message: %1', $e->getMessage())
                );
            }
        }

        $resultRedirect->setUrl($this->url->getUrl('marketplace/order/message', [
            'order_id'  => $order->getId(),
            'remote_id' => $miraklOrder->getId(),
        ]));

        return $resultRedirect;
    }

    /**
     * @param string $recipients
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
