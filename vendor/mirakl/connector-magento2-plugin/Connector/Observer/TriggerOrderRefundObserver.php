<?php
namespace Mirakl\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Mirakl\Api\Helper\Payment as PaymentApi;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\MMP\Common\Domain\Payment\PaymentStatus;

class TriggerOrderRefundObserver implements ObserverInterface
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderResourceFactory
     */
    protected $orderResourceFactory;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;
    
    /**
     * @var PaymentApi
     */
    protected $paymentApi;

    /**
     * @param   OrderFactory            $orderFactory
     * @param   OrderResourceFactory    $orderResourceFactory
     * @param   OrderHelper             $orderHelper
     * @param   PaymentApi              $paymentApi
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderResourceFactory $orderResourceFactory,
        OrderHelper $orderHelper,
        PaymentApi $paymentApi
    ) {
        $this->orderFactory         = $orderFactory;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->orderHelper          = $orderHelper;
        $this->paymentApi           = $paymentApi;
    }

    /**
     * Method used by PSP to trigger order refund on an order by just dispatching an event
     * called 'mirakl_trigger_order_refund' with the following parameters:
     *
     * increment_id             Magento order (required)
     * order_id                 @deprecated Use increment_id instead
     * mirakl_order_id          Mirakl order (required)
     * status                   Status of the refund payment (OK or REFUSED) (optional, OK if empty)
     * refund_id                Mirakl refund id (optional, will validate all refunds of the order if empty)
     * transaction_number       Transaction number of the refund (optional but recommended)
     * transaction_date         Transaction date of the refund (optional but recommended)
     *
     * {@inheritdoc}
     * @throws  \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\Event $event */
        $event = $observer->getEvent();

        $incrementId = $event->getIncrementId();
        if (!$incrementId) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderFactory->create();
            $orderId = $event->getOrderId();
            $this->orderResourceFactory->create()->load($order, $orderId);
            $incrementId = $order->getIncrementId();

            if (!$order->getId()) {
                throw new \Exception('Unable to find the Magento order.');
            }
        }

        $miraklOrderId = $event->getMiraklOrderId();
        if (!$miraklOrderId) {
            $miraklOrderId = $event->getRemoteId(); // backward compatibility for remote_id parameter
        }

        $miraklOrder = $this->orderHelper->getMiraklOrderById($incrementId, $miraklOrderId);
        if (!$miraklOrder) {
            throw new \Exception(sprintf('Mirakl order with id %s could not be found.', $miraklOrderId));
        }

        $status = $event->getStatus();
        if (!$status) {
            $status = PaymentStatus::OK;
        }

        $this->paymentApi->refundPayment(
            $miraklOrder,
            $status,
            $event->getRefundId(),
            $event->getTransactionNumber(),
            $event->getTransactionDate()
        );
    }
}