<?php
declare(strict_types=1);

namespace Mirakl\GraphQl\Model\Resolver\Order;

use GraphQL\Error\ClientAware;
use GuzzleHttp\Exception\ClientException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Mirakl\Api\Helper\Order as OrderHelper;
use Mirakl\GraphQl\Model\Resolver\AbstractResolver;
use Mirakl\MMP\FrontOperator\Domain\Order;

abstract class AbstractOrderResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @param  OrderHelper  $orderHelper
     */
    public function __construct(OrderHelper $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param  string $orderId
     * @param  string $currentUserId
     * @return Order
     * @throws ClientAware
     */
    protected function getOrder($orderId, $currentUserId = null)
    {
        try {
            $orders = $this->orderHelper->getOrders(['order_ids' => $orderId]);
        } catch (ClientException $e) {
            throw $this->mapSdkError($e);
        }

        if (!$orders->count()) {
            throw new GraphQlNoSuchEntityException(__('Order not found', $orderId));
        }

        $order = $orders->first();
        if ($currentUserId != null && $order->getCustomer()->getId() != $currentUserId) {
            throw new GraphQlNoSuchEntityException(__('Order not found'));
        }

        return $order;
    }
}
