<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\MiraklConnector\Rewrite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Mirakl\Api\Helper\Payment as PaymentApi;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\MMP\Common\Domain\Payment\PaymentStatus;
use Mirakl\Connector\Observer\TriggerOrderRefundObserver as MiraklTriggerOrderRefundObserver;
use MiraklSeller\Sales\Helper\CreditMemo as CreditMemoHelper;
use MiraklSeller\Sales\Model\Synchronize\CreditMemo as CreditMemoSynchronizer;
use Retailplace\MiraklSeller\Rewrite\MiraklSeller\Sales\Model\Create\Refund as RefundCreator;
use Retailplace\MiraklConnector\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Mirakl\MMP\Common\Domain\Order\Refund;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use Exception;

class TriggerOrderRefundObserver extends MiraklTriggerOrderRefundObserver
{
    /**
     * @var OrderResourceFactory
     */
    protected $orderResourceFactory;

    /**
     * @var RefundCreator
     */
    protected $refundCreator;

    /**
     * @var CreditMemoSynchronizer
     */
    protected $creditMemoSynchronizer;

    /**
     * @var CreditMemoHelper
     */
    protected $creditMemoHelper;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param OrderFactory $orderFactory
     * @param OrderResourceFactory $orderResourceFactory
     * @param OrderHelper $orderHelper
     * @param PaymentApi $paymentApi
     * @param RefundCreator $refundCreator
     * @param CreditMemoSynchronizer $creditMemoSynchronizer
     * @param CreditMemoHelper $creditMemoHelper
     * @param Logger $logger
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderResourceFactory $orderResourceFactory,
        OrderHelper $orderHelper,
        PaymentApi $paymentApi,
        RefundCreator $refundCreator,
        CreditMemoSynchronizer $creditMemoSynchronizer,
        CreditMemoHelper $creditMemoHelper,
        Logger $logger
    ) {
        parent::__construct($orderFactory, $orderResourceFactory, $orderHelper, $paymentApi);
        $this->refundCreator = $refundCreator;
        $this->creditMemoSynchronizer = $creditMemoSynchronizer;
        $this->creditMemoHelper = $creditMemoHelper;
        $this->logger = $logger;
    }

    /**
     * Method used by PSP to trigger order refund on an order by just dispatching an event
     * called 'mirakl_trigger_order_refund' with the following parameters:
     *
     * increment_id             Magento order (required)
     * order_id                 @throws  Exception
     * @deprecated Use increment_id instead
     * mirakl_order_id          Mirakl order (required)
     * status                   Status of the refund payment (OK or REFUSED) (optional, OK if empty)
     * refund_id                Mirakl refund id (optional, will validate all refunds of the order if empty)
     * transaction_number       Transaction number of the refund (optional but recommended)
     * transaction_date         Transaction date of the refund (optional but recommended)
     *
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $incrementId = $event->getIncrementId();
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

        if (!$order->getId()) {
            echo sprintf('Unable to find the Magento order %s.', $incrementId);
            return;
        }

        $miraklOrderId = $event->getMiraklOrderId();
        if (!$miraklOrderId) {
            $miraklOrderId = $event->getRemoteId(); // backward compatibility for remote_id parameter
        }

        $miraklOrder = $this->orderHelper->getMiraklOrderById($incrementId, $miraklOrderId);
        if (!$miraklOrder) {
            throw new Exception(sprintf('Mirakl order with id %s could not be found.', $miraklOrderId));
        }

        $status = $event->getStatus();
        if (!$status) {
            $status = PaymentStatus::OK;
        }

        $isExistWaitingRefund = false;
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            foreach ($orderLine->getRefunds() as $refundIndex => $refund) {
                if ($refund->getState() == "REFUNDED") {
                    $orderLine->getRefunds()->remove($refundIndex);
                } else {
                    $isExistWaitingRefund = true;
                }
            }
        }

        if (!$isExistWaitingRefund) {
            $message = sprintf('There is no item in mirakl order %s for which a return can be made.', $miraklOrderId);
            $this->logger->warning($message);
            return;
        }

        /********* Partial Refund   **********/
        try {
            $infoMessage = "";
            $invoice = $order->getInvoiceCollection()->getFirstItem();
            if (!$invoice) {
                throw new Exception(sprintf('Invoice for order %s could not be found.', $order->getIncrementId()));
            }

            $refunds = [];
            $miraklOrderLines = [];
            /** @var ShopOrderLine $orderLine */
            foreach ($miraklOrder->getOrderLines() as $orderLine) {
                /** @var Refund $refund */
                foreach ($orderLine->getRefunds() as $refund) {
                    $existingCreditMemo = $this->creditMemoHelper->getCreditMemoByMiraklRefundId($refund->getId());
                    if ($existingCreditMemo->getId()) {
                        $infoMessage = "Creditmemo {$existingCreditMemo->getId()}
                            synchronized for order id {$incrementId}";
                        $this->creditMemoSynchronizer->synchronize($existingCreditMemo, $refund);
                    } elseif ($order->canCreditmemo()) {
                        $infoMessage = "Creditmemo generated for order id {$incrementId}";
                        $refunds[$refund->getId()] = $refund;
                        $miraklOrderLines[$orderLine->getId()] = $orderLine;
                    }
                }
            }

            $this->refundCreator->createCombined($order, $miraklOrderLines, $refunds, $invoice);

            try {
                $this->paymentApi->refundPayment(
                    $miraklOrder,
                    $status,
                    $event->getRefundId(),
                    $event->getTransactionNumber(),
                    $event->getTransactionDate()
                );
            } catch (Exception $e) {
                $this->logger->critical("Refund: " . $e->getMessage() . "\r\nTrace: " . $e->getTraceAsString());
            }
        } catch (Exception | LocalizedException $e) {
            $errorMessage = "Creditmemo: " . $e->getMessage() . "\r\n"
                . $infoMessage . "\r\nTrace: " . $e->getTraceAsString();
            $this->logger->critical($errorMessage);
        }
    }
}
