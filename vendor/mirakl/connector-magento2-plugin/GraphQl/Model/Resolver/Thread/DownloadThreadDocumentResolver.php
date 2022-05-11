<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Thread;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class DownloadThreadDocumentResolver extends AbstractThreadResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->checkLoggedCustomer($context);

        $attachmentId = $this->getInput($args, 'attachment_id', true);

        $attachment = null;
        try {
            $attachment = $this->messageHelper->downloadThreadMessageAttachment($attachmentId);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        return $this->messageHelper->transformToAttachment($attachment);
    }
}
