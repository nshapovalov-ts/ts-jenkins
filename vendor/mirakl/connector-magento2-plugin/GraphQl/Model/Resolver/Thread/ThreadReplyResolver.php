<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Thread;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;

class ThreadReplyResolver extends AbstractThreadResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $threadId = $this->getInput($args, 'input.thread_id', true);
        $messageInput = $this->getInput($args, 'input.message_input', true);
        $files = $this->getInput($args, 'input.files');

        // Control than customer can reply to this tread
        $this->getThread($threadId, $currentUserId);

        $input = new ThreadReplyMessageInput($messageInput);

        $attachments = null;
        if ($files && count($files)) {
            $attachments = [];
            foreach ($files as $file) {
                $attachments[] = $this->messageHelper->transformToFileWrapper($file);
            }
        }

        $replyCreated = null;
        try {
            $replyCreated = $this->messageHelper->replyToThread($threadId, $input, $attachments);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        $data = $replyCreated->toArray();
        $data['model'] = $replyCreated;

        return $data;
    }
}
