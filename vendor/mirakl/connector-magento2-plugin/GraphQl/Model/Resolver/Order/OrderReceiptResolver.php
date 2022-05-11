<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Order;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class OrderReceiptResolver extends AbstractOrderResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $orderId = $this->getInput($args, 'mp_order_id', true);

        $order = $this->getOrder($orderId, $currentUserId);

        try {
            $this->orderHelper->receiveOrder($order);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        return true;
    }
}
