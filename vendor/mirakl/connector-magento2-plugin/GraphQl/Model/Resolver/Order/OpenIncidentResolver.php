<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Order;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class OpenIncidentResolver extends AbstractOrderResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();

        $this->checkLoggedCustomer($context);

        $orderId = $this->getInput($args, 'input.mp_order_id', true);
        $orderLineId = $this->getInput($args, 'input.mp_order_line_id', true);
        $reasonCode = $this->getInput($args, 'input.reason_code', true);

        $order = $this->getOrder($orderId, $currentUserId);

        try {
            $this->orderHelper->openIncident($order, $orderLineId, $reasonCode);
        } catch (\Exception $e) {
            throw $this->mapSdkError($e);
        }

        return true;
    }
}
