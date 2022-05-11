<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Order;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mirakl\Api\Helper\Message;
use Mirakl\Api\Helper\Order as OrderHelper;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;

class OrderThreadResolver extends AbstractOrderResolver implements ResolverInterface
{
    /**
     * @var Message
     */
    protected $messageHelper;

    /**
     * @param  OrderHelper  $orderHelper
     * @param  Message      $messageHelper
     */
    public function __construct(OrderHelper $orderHelper, Message $messageHelper)
    {
        parent::__construct($orderHelper);
        $this->messageHelper = $messageHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $orderId = $this->getInput($args, 'input.mp_order_id', true);
        $threadInput = $this->getInput($args, 'input.thread_input', true);
        $files = $this->getInput($args, 'input.files');

        $order = $this->getOrder($orderId, $currentUserId);

        $thread = new CreateOrderThread($threadInput);

        $attachments = [];
        if ($files && count($files)) {
            foreach ($files as $file) {
                $attachments[] = $this->messageHelper->transformToFileWrapper($file);
            }
        }

        $threadCreated = null;
        try {
            $threadCreated = $this->orderHelper->createOrderThread($order, $thread, $attachments);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        $data = $threadCreated->toArray();
        $data['model'] = $threadCreated;

        return $data;
    }
}
