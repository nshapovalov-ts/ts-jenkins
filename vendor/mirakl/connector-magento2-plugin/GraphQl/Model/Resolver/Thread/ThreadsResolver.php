<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Thread;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ThreadsResolver extends AbstractThreadResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $seekCollection = null;
        $pageToken = $this->getInput($args, 'page_token');
        if ($pageToken) {
            try {
                $seekCollection = $this->messageHelper->getThreadsFromPageToken($pageToken);
            } catch (\Exception $e) {
                throw $this->mapSdkError($e);
            }
        } else {
            $entityType = $this->getInput($args, 'input.entity_type');
            $entityId = $this->getInput($args, 'input.entity_id');
            $limit = $this->getInput($args, 'limit');

            try {
                $seekCollection = $this->messageHelper->getThreads($currentUserId, $entityType, $entityId, $limit);
            } catch (\Exception $e) {
                throw $this->mapSdkError($e);
            }
        }

        return [
            'model' => $seekCollection,
            'data' => $seekCollection->getCollection()->toArray(),
            'next_page_token' => $seekCollection->getNextPageToken(),
            'previous_page_token' => $seekCollection->getPreviousPageToken(),
        ];
    }
}
