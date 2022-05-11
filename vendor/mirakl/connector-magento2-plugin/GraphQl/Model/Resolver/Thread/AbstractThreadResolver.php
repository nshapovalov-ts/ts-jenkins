<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Thread;

use GraphQL\Error\ClientAware;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Mirakl\Api\Helper\Message;
use Mirakl\GraphQl\Model\Resolver\AbstractResolver;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;

abstract class AbstractThreadResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var Message
     */
    protected $messageHelper;

    /**
     * @param  Message  $messageHelper
     */
    public function __construct(Message $messageHelper)
    {
        $this->messageHelper = $messageHelper;
    }

    /**
     * @param  string   $threadId
     * @param  string   $userId
     * @return ThreadDetails
     * @throws ClientAware
     */
    protected function getThread($threadId, $userId = null)
    {
        try {
            return $this->messageHelper->getThreadDetails($threadId, $userId);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }
    }
}
