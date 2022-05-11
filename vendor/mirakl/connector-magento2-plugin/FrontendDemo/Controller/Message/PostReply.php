<?php
namespace Mirakl\FrontendDemo\Controller\Message;

use GuzzleHttp\Exception\BadResponseException;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;

class PostReply extends AbstractMessage
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
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
                $fileData = $this->getRequest()->getFiles('file');
                if ($fileData && !empty($fileData['tmp_name'])) {
                    $file = new FileWrapper(new \SplFileObject($fileData['tmp_name']));
                    $file->setContentType($fileData['type']);
                    $file->setFileName($fileData['name']);
                    $files[] = $file;
                }

                $this->messageApi->replyToThread($thread->getId(), new ThreadReplyMessageInput($messageInput), $files);

                $this->messageManager->addSuccessMessage(__('Your message has been sent successfully.'));

                $this->session->setFormData([]);
            } catch (BadResponseException $e) {
                $message = $e->getMessage();
                $response = \Mirakl\parse_json_response($e->getResponse());
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
            } catch (\Exception $e) {
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
     * @param   ThreadDetails   $thread
     * @param   string          $recipients
     * @return  array
     */
    protected function getTo(ThreadDetails $thread, $recipients)
    {
        $to = [];

        $addSeller   = ($recipients === 'SHOP' || $recipients === 'BOTH');
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
