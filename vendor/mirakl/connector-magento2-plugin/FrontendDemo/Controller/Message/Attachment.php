<?php
namespace Mirakl\FrontendDemo\Controller\Message;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Raw;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;

class Attachment extends AbstractMessage
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            if (!$this->validateFormKey()) {
                return $this->_redirect($this->_redirect->getRefererUrl());
            }

            $attachmentId = $this->getRequest()->getParam('id');
            if (!$this->validateAttachment($attachmentId)) {
                $this->messageManager->addErrorMessage(__('Attachment not found.'));

                return $this->_redirect($this->_redirect->getRefererUrl());
            }

            $document = $this->messageApi->downloadThreadMessageAttachment($attachmentId);

            /** @var Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $contentSize = $document->getFile()->fstat()['size'];

            $result->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', $document->getContentType(), true)
                ->setHeader('Content-Length', $contentSize)
                ->setHeader('Content-Disposition', 'attachment; filename=' . $document->getFileName());
            $result->setContents($document->getFile()->fread($contentSize));

            return $result;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->_redirect('*/*/view', ['id' => $this->getRequest()->getParam('id')]);
        }
    }

    /**
     * @param   string  $attachmentId
     * @return  bool
     */
    protected function validateAttachment($attachmentId)
    {
        if (!$thread = $this->getThread()) {
            return false;
        }

        /** @var ThreadMessage $message */
        foreach ($thread->getMessages() as $message) {
            if (!empty($message->getAttachments())) {
                /** @var ThreadAttachment $attachment */
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->getId() == $attachmentId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
