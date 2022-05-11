<?php
namespace Mirakl\Connector\Plugin\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mirakl\Connector\Helper\Config as ConfigHelper;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Psr\Log\LoggerInterface;

class OrderSavePlugin
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   OrderHelper     $orderHelper
     * @param   ConfigHelper    $configHelper
     * @param   LoggerInterface $logger
     */
    public function __construct(
        OrderHelper $orderHelper,
        ConfigHelper $configHelper,
        LoggerInterface $logger
    ) {
        $this->orderHelper = $orderHelper;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Mirakl Order is created in this plugin because order item id used for setOrderLineId may not be set in
     * sales_order_save_after event and we also need taxes to be registered in @see \Magento\Tax\Model\Plugin\OrderSave
     *
     * @param   OrderRepositoryInterface    $subject
     * @param   OrderInterface              $order
     * @return  OrderInterface
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        if (!$this->configHelper->isAutoCreateOrder()) {
            return $order;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $validStatus = in_array($order->getStatus(), $this->configHelper->getCreateOrderStatuses());
        $alreadySent = $order->getData('mirakl_sent');

        if ($validStatus && !$alreadySent && $this->orderHelper->isMiraklOrder($order)) {
            try {
                $this->orderHelper->createMiraklOrder($order);
            } catch (\Exception $e) {
                // Ignore to avoid errors in frontend
                $this->logger->warning($e->getMessage());
            }
        }

        return $order;
    }
}