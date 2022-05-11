<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Thread;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ThreadDetailResolver extends AbstractThreadResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $thread = $this->getThread($this->getInput($args, 'thread_id', true), $currentUserId);

        $data = $thread->toArray();
        $data['model'] = $thread;

        return $data;
    }
}
