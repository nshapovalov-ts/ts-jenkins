<?php
namespace Mirakl\Api\Helper;

use Mirakl\Core\Domain\Collection\FileCollection;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyCreated;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;
use Mirakl\MMP\Common\Request\Message\DownloadThreadMessageAttachmentRequest;
use Mirakl\MMP\Common\Request\Message\ThreadReplyRequest;
use Mirakl\MMP\Front\Request\Message\GetThreadDetailsRequest;
use Mirakl\MMP\Front\Request\Message\GetThreadsRequest;

class Message extends ClientHelper\MMP
{
    /**
     * (M10) Retrieve a thread
     *
     * @param   string       $threadId
     * @param   string|null  $customerId
     * @return  ThreadDetails
     */
    public function getThreadDetails($threadId, $customerId = null)
    {
        $request = new GetThreadDetailsRequest($threadId);

        if ($customerId) {
            $request->setCustomerId($customerId);
        }

        $this->_eventManager->dispatch('mirakl_api_get_thread_details_before', ['request' => $request]);

        return $this->send($request);
    }

    /**
     * (M11) List all threads
     *
     * @param   string|null         $customerId
     * @param   string|null         $entityType
     * @param   array|string|null   $entityId
     * @param   int|null            $limit
     * @param   string|null         $token
     * @return  SeekableCollection
     */
    public function getThreads($customerId = null, $entityType = null, $entityId = null, $limit = null, $token = null)
    {
        $request = new GetThreadsRequest();

        if ($customerId) {
            $request->setCustomerId($customerId);
        }

        if ($entityType) {
            $request->setEntityType($entityType);
        }

        if ($entityId) {
            $request->setEntityId($entityId);
        }

        if ($limit) {
            $request->setLimit($limit);
        }

        if ($token) {
            $request->setPageToken($token);
        }

        $this->_eventManager->dispatch('mirakl_api_get_threads_before', ['request' => $request]);

        return $this->send($request);
    }

    /**
     * (M11) List all threads from page token
     *
     * @param   string  $token
     * @return  SeekableCollection
     */
    public function getThreadsFromPageToken($token)
    {
        return $this->getThreads(null, null, null, null, $token);
    }

    /**
     * (M12) Reply to a thread
     *
     * @param  string                   $threadId
     * @param  ThreadReplyMessageInput  $messageInput
     * @param  FileWrapper[]            $files
     * @return ThreadReplyCreated
     */
    public function replyToThread($threadId, ThreadReplyMessageInput $messageInput, $files = null)
    {
        $request = new ThreadReplyRequest($threadId, $messageInput);

        if ($files && count($files)) {
            $request->setFiles(new FileCollection($files));
        }

        $this->_eventManager->dispatch('mirakl_api_reply_to_thread_before', ['request' => $request]);

        return $this->send($request);
    }

    /**
     * (M13) Download an attachment
     *
     * @param   string  $attachmentId
     * @return  FileWrapper
     */
    public function downloadThreadMessageAttachment($attachmentId)
    {
        $request = new DownloadThreadMessageAttachmentRequest($attachmentId);

        $this->_eventManager->dispatch('mirakl_api_download_thread_message_attachment_before', ['request' => $request]);

        return $this->send($request);
    }

    /**
     * @param   FileWrapper $fileWrapper
     * @return  array
     */
    public function transformToAttachment(FileWrapper $fileWrapper)
    {
        if (!$file = $fileWrapper->getFile()) {
            return null;
        }

        $file->rewind();

        return [
            'base64_encoded_data' => base64_encode(@$file->fread($file->fstat()['size'])),
            'name' => $fileWrapper->getFileName(),
            'type' => $fileWrapper->getContentType(),
        ];
    }

    /**
     * @param   array   $fileInput
     * @return  FileWrapper
     */
    public function transformToFileWrapper(array $fileInput)
    {
        $file = new FileWrapper(base64_decode($fileInput['base64_encoded_data']));
        $file->setFileName($fileInput['name']);
        $file->setContentType($fileInput['type']);

        return $file;
    }
}
