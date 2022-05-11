<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Controller\Message;

use GuzzleHttp\Exception\BadResponseException;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;
use Mirakl\FrontendDemo\Controller\Message\PostReply as MessagePostReply;
use Exception;
use function Mirakl\parse_json_response;
use SplFileObject;

/**
 * Class PostReply
 */
class PostReply extends MessagePostReply
{
    /**
     * type float
     */
    const FILE_MAX_SIZE = 10.0;

    /**
     * Supported formats are:
     * PDF, JPEG, GIF, PNG, TIFF, ZIP, MOV, MP4, text files,
     * MS Office formats, and Open Office formats.
     */
    const SUPPORT_FORMATS = [
        'pdf',
        'jpeg',
        'jpg',
        'gif',
        'png',
        'tiff',
        'zip',
        'mov',
        'mp4',
        'txt',
        'doc',
        'dot',
        'docx',
        'xls',
        'xlsx',
        'xml',
        'ods',
        'ots',
        'odt',
        'ott',
        'oth',
        'odm'
    ];

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $errorMessages = [];

        if (!$this->validateFormKey()) {
            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        if (!$thread = $this->getThread()) {
            $this->messageManager->addErrorMessage(__('Thread not found.'));

            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        $data = $this->getRequest()->getPostValue();

        if (!empty($data)) {
            try {
                $messageInput = [
                    'body' => $data['edit_body'],
                    'to'   => $this->getTo($thread, $data['edit_recipients']),
                ];

                if (!empty($data['edit_subject'])) {
                    $messageInput['topic'] = [
                        'type'  => 'FREE_TEXT',
                        'value' => $data['edit_subject'],
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
                                if ($fileSize > self::FILE_MAX_SIZE) {
                                    $errorMessages[] = __(
                                        "The file was too big and couldn't be uploaded. "
                                        . "Use a file smaller than %1 MBs and try to upload again.",
                                        (int) self::FILE_MAX_SIZE
                                    );
                                }
                            }

                            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

                            if (!in_array(strtolower($fileExtension), self::SUPPORT_FORMATS)) {
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
                    return $this->_redirect($this->_url->getUrl('marketplace/message/view', [
                        'thread' => $thread->getId(),
                    ]));
                }

                $this->messageApi->replyToThread($thread->getId(), new ThreadReplyMessageInput($messageInput), $files);

                $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));

                $this->session->setFormData([]);
            } catch (BadResponseException $e) {
                $message = $e->getMessage();
                $response = parse_json_response($e->getResponse());
                if (!empty($response['message'])) {
                    $message = $response['message'];
                } elseif (!empty($response['errors'][0]['message'])) {
                    $message = $response['errors'][0]['message'];
                }
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

        return $this->_redirect($this->_url->getUrl('marketplace/message/view', [
            'thread' => $thread->getId(),
        ]));
    }

    /**
     * @param ThreadDetails $thread
     * @param string $recipients
     * @return  array
     */
    protected function getTo(ThreadDetails $thread, $recipients)
    {
        $to = [];

        $addSeller = ($recipients === 'SHOP' || $recipients === 'BOTH');
        $addOperator = ($recipients === 'OPERATOR' || $recipients === 'BOTH');

        /** @var ThreadParticipant $participant */
        foreach ($thread->getAuthorizedParticipants() as $participant) {
            if ($participant->getType() == 'SHOP' && $addSeller) {
                $to[] = ['type' => 'SHOP', 'id' => $participant->getId()];
            } elseif ($participant->getType() == 'OPERATOR' && $addOperator) {
                $to[] = ['type' => 'OPERATOR'];
            }
        }

        return $to;
    }
}
