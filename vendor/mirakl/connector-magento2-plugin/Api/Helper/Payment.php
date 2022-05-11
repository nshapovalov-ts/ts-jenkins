<?php
namespace Mirakl\Api\Helper;

use Magento\Framework\Exception\LocalizedException;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
use Mirakl\MMP\Common\Domain\Payment\PaymentStatus;
use Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Debit\DebitOrderCollection;
use Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Debit\OrderPaymentCollection;
use Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Refund\OrderLineRefundCollection;
use Mirakl\MMP\FrontOperator\Domain\Collection\Payment\Refund\RefundOrderCollection;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;
use Mirakl\MMP\FrontOperator\Domain\Payment\Debit\OrderPayment;
use Mirakl\MMP\FrontOperator\Domain\Payment\Refund\OrderLineRefund;
use Mirakl\MMP\FrontOperator\Request\Payment\Debit\ConfirmOrderDebitRequest;
use Mirakl\MMP\FrontOperator\Request\Payment\Debit\GetOrderDebitsRequest;
use Mirakl\MMP\FrontOperator\Request\Payment\Refund\ConfirmOrderRefundRequest;
use Mirakl\MMP\FrontOperator\Request\Payment\Refund\GetOrderRefundsRequest;

class Payment extends ClientHelper\MMP
{
    const PAY_ON_DELIVERY   = 'PAY_ON_DELIVERY';
    const PAY_ON_ACCEPTANCE = 'PAY_ON_ACCEPTANCE';
    const PAY_ON_DUE_DATE   = 'PAY_ON_DUE_DATE';

    /**
     * @param   MiraklOrder $miraklOrder
     * @return  bool
     */
    public function canDebitOrder(MiraklOrder $miraklOrder)
    {
        return !$miraklOrder->getCustomerDebitedDate() && !in_array($miraklOrder->getStatus()->getState(), [
            OrderState::STAGING,
            OrderState::WAITING_ACCEPTANCE,
            OrderState::CLOSED,
            OrderState::REFUSED,
            OrderState::CANCELED,
        ]);
    }

    /**
     * @param   MiraklOrder\OrderLine   $miraklOrderLine
     * @param   MiraklOrder             $miraklOrder
     * @return  bool
     */
    public function canDebitOrderLine(MiraklOrder\OrderLine $miraklOrderLine, MiraklOrder $miraklOrder)
    {
        if (!$this->canDebitOrder($miraklOrder)) {
            return false;
        }

        $orderLineState = $miraklOrderLine->getStatus()->getState();

        if ($miraklOrder->getPaymentWorkflow() == self::PAY_ON_DELIVERY) {
            return in_array($orderLineState, self::getOrderStatusesForPaymentMethod(self::PAY_ON_DELIVERY));
        }

        return $orderLineState == OrderState::WAITING_DEBIT_PAYMENT;
    }

    /**
     * (PA01) Validates or refuses Mirakl order payments
     *
     * @param   OrderPaymentCollection  $orderPayments
     */
    public function confirmOrderDebit(OrderPaymentCollection $orderPayments)
    {
        $request = new ConfirmOrderDebitRequest($orderPayments);

        $this->_eventManager->dispatch('mirakl_api_confirm_order_debit_before', [
            'request'  => $request,
            'payments' => $orderPayments,
        ]);

        $this->send($request);
    }

    /**
     * (PA02) Validates or refuses Mirakl order refunds
     *
     * @param   OrderLineRefundCollection   $orderLineRefunds
     */
    public function confirmOrderRefund(OrderLineRefundCollection $orderLineRefunds)
    {
        $request = new ConfirmOrderRefundRequest($orderLineRefunds);

        $this->_eventManager->dispatch('mirakl_api_confirm_order_refund_before', [
            'request' => $request,
            'refunds' => $orderLineRefunds,
        ]);

        $this->send($request);
    }

    /**
     * Validates payment of a Mirakl order
     *
     * @param   MiraklOrder         $miraklOrder
     * @param   string              $customerId
     * @param   string              $status
     * @param   string              $transactionNumber
     * @param   string|\DateTime    $transactionDate
     * @throws  LocalizedException
     */
    public function debitPayment(
        MiraklOrder $miraklOrder,
        $customerId,
        $status = PaymentStatus::OK,
        $transactionNumber = null,
        $transactionDate = null
    ) {
        if (!$this->canDebitOrder($miraklOrder)) {
            throw new LocalizedException(__('The Mirakl order "%1" cannot be debited.', $miraklOrder->getId()));
        }

        // Total price from Mirakl is excluding tax
        $totalPrice = $miraklOrder->getTotalPrice();

        // Add tax amount
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            /** @var MiraklOrder\OrderLine $orderLine */
            if (!$this->canDebitOrderLine($orderLine, $miraklOrder)) {
                continue;
            }

            /** @var OrderTaxAmount $tax */
            foreach ($orderLine->getTaxes() as $tax) {
                $totalPrice += $tax->getAmount();
            }
            foreach ($orderLine->getShippingTaxes() as $tax) {
                $totalPrice += $tax->getAmount();
            }
        }

        $orderPayment = new OrderPayment();
        $orderPayment->setOrderId($miraklOrder->getId());
        $orderPayment->setCustomerId($customerId);
        $orderPayment->setPaymentStatus($status);
        $orderPayment->setAmount($totalPrice);
        $orderPayment->setCurrencyIsoCode($miraklOrder->getCurrencyIsoCode());

        if ($transactionNumber) {
            $orderPayment->setTransactionNumber($transactionNumber);
        }

        if ($transactionDate) {
            $orderPayment->setTransactionDate($transactionDate);
        }

        $this->_eventManager->dispatch('mirakl_api_debit_payment_before', [
            'payment'      => $orderPayment,
            'mirakl_order' => $miraklOrder,
        ]);

        $orderPayments = new OrderPaymentCollection();
        $orderPayments->add($orderPayment);

        $this->confirmOrderDebit($orderPayments);
    }

    /**
     * (PA11) List ALL pending order debits in "PAY_ON_ACCEPTANCE" workflow and order status is "WAITING_DEBIT_PAYMENT"
     *
     * @return  DebitOrderCollection
     */
    public function getAllOrderDebits()
    {
        $offset = 0;
        $max = 100;
        $debits = [];
        while (true) {
            $result = $this->getOrderDebits(true, $offset, $max);
            $debits = array_merge($debits, $result->getItems());
            if (!$result->count() || count($debits) >= $result->getTotalCount()) {
                break;
            }
            $offset += $max;
        }

        return new DebitOrderCollection($debits, count($debits));
    }

    /**
     * (PA11) List pending order debits in "PAY_ON_ACCEPTANCE" workflow and order status is "WAITING_DEBIT_PAYMENT"
     *
     * @param   bool    $paginate
     * @param   int     $offset
     * @param   int     $max
     * @return  DebitOrderCollection
     */
    public function getOrderDebits($paginate = false, $offset = 0, $max = 10)
    {
        $request = new GetOrderDebitsRequest();

        $request->setPaginate($paginate);
        if (true === $paginate) {
            $request->setOffset($offset);
            $request->setMax($max);
        }

        $this->_eventManager->dispatch('mirakl_api_get_order_debits_before', [
            'request'  => $request,
            'paginate' => $paginate,
            'offset'   => $offset,
            'max'      => $max,
        ]);

        return $this->send($request);
    }

    /**
     * (PA12) Lists ALL order refunds where refund state is RefundState::WAITING_REFUND_PAYMENT
     *
     * @return  RefundOrderCollection
     */
    public function getAllOrderRefunds()
    {
        $offset = 0;
        $max = 100;
        $refunds = [];
        while (true) {
            $result = $this->getOrderRefunds(true, $offset, $max);
            $refunds = array_merge($refunds, $result->getItems());
            if (!$result->count() || count($refunds) >= $result->getTotalCount()) {
                break;
            }
            $offset += $max;
        }

        return new RefundOrderCollection($refunds, count($refunds));
    }

    /**
     * (PA12) Lists order refunds where refund state is RefundState::WAITING_REFUND_PAYMENT
     *
     * @param   bool    $paginate
     * @param   int     $offset
     * @param   int     $max
     * @return  RefundOrderCollection
     */
    public function getOrderRefunds($paginate = false, $offset = 0, $max = 10)
    {
        $request = new GetOrderRefundsRequest();

        $request->setPaginate($paginate);
        if (true === $paginate) {
            $request->setOffset($offset);
            $request->setMax($max);
        }

        $this->_eventManager->dispatch('mirakl_api_get_order_refunds_before', [
            'request'  => $request,
            'paginate' => $paginate,
            'offset'   => $offset,
            'max'      => $max,
        ]);

        return $this->send($request);
    }

    /**
     * @param   string  $paymentMethod
     * @return  array
     */
    public static function getOrderStatusesForPaymentMethod($paymentMethod = self::PAY_ON_ACCEPTANCE)
    {
        switch ($paymentMethod) {
            case self::PAY_ON_DELIVERY:
                $statuses = [
                    OrderState::WAITING_DEBIT_PAYMENT,
                    OrderState::SHIPPING,
                    OrderState::SHIPPED,
                    OrderState::TO_COLLECT,
                    OrderState::INCIDENT_OPEN,
                ];
                break;
            default:
                $statuses = [OrderState::WAITING_DEBIT_PAYMENT];
        }

        return $statuses;
    }

    /**
     * Refund payment of a Mirakl order
     *
     * @param   MiraklOrder         $miraklOrder
     * @param   string              $status
     * @param   string              $refundId
     * @param   string              $transactionNumber
     * @param   string|\DateTime    $transactionDate
     */
    public function refundPayment(
        MiraklOrder $miraklOrder,
        $status = PaymentStatus::OK,
        $refundId = null,
        $transactionNumber = null,
        $transactionDate = null
    ) {
        $refundIds = [];

        /** @var MiraklOrder\OrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Refund $refund */
            foreach ($orderLine->getRefunds() as $refund) {
                if (!$refundId || $refundId == $refund->getId()) {
                    $refundAmount = $refund->getAmount() + $refund->getShippingAmount();

                    // Add taxes if available
                    /** @var \Mirakl\MMP\Common\Domain\Collection\Order\Tax\OrderTaxAmountCollection $taxes */
                    $taxes = $refund->getTaxes();
                    if (count($taxes)) {
                        foreach ($taxes as $tax) {
                            $refundAmount += $tax->getAmount();
                        }
                    }

                    // Add shipping taxes if available
                    /** @var \Mirakl\MMP\Common\Domain\Collection\Order\Tax\OrderTaxAmountCollection $shippingTaxes */
                    $shippingTaxes = $refund->getShippingTaxes();
                    if (count($shippingTaxes)) {
                        foreach ($shippingTaxes as $shippingTax) {
                            $refundAmount += $shippingTax->getAmount();
                        }
                    }

                    $refundIds[$refund->getId()] = $refundAmount;
                }
            }
        }

        if (!empty($refundIds)) {
            $orderLineRefunds = new OrderLineRefundCollection();

            foreach ($refundIds as $refundId => $refundAmount) {
                $orderLineRefund = new OrderLineRefund();
                $orderLineRefund->setRefundId($refundId);
                $orderLineRefund->setPaymentStatus($status);
                $orderLineRefund->setAmount($refundAmount);
                $orderLineRefund->setCurrencyIsoCode($miraklOrder->getCurrencyIsoCode());

                if ($transactionNumber) {
                    $orderLineRefund->setTransactionNumber($transactionNumber);
                }

                if ($transactionDate) {
                    $orderLineRefund->setTransactionDate($transactionDate);
                }

                $this->_eventManager->dispatch('mirakl_api_refund_payment_before', [
                    'refund'       => $orderLineRefund,
                    'mirakl_order' => $miraklOrder,
                ]);

                $orderLineRefunds->add($orderLineRefund);
            }

            $this->confirmOrderRefund($orderLineRefunds);
        }
    }
}
