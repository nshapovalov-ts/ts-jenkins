<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Rewrite\MiraklSeller\Sales\Model\Create;

use Exception;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo as CreditMemo;
use Magento\Sales\Model\Order\CreditmemoFactory as CreditMemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Mirakl\MMP\Common\Domain\Order\Refund as MiraklRefund;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use MiraklSeller\Sales\Helper\CreditMemo as CreditMemoHelper;
use Magento\Sales\Model\Service\CreditmemoService;
use MiraklSeller\Sales\Model\Create\Refund as MiraklSellerRefund;
use Psr\Log\LoggerInterface;
use Magento\Tax\Model\Calculation;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Refund
 */
class Refund extends MiraklSellerRefund
{
    /**
     * @var CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Calculation
     */
    private $calculationTool;

    /**
     * @var Item
     */
    private $taxItem;

    /**
     * @var array
     */
    private $taxItems;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * Refund constructor.
     *
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \MiraklSeller\Sales\Helper\CreditMemo $creditMemoHelper
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     * @param \Magento\Tax\Model\Calculation $calculationTool
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\Tax\Item $taxItem
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        CreditMemoHelper $creditMemoHelper,
        CreditMemoFactory $creditMemoFactory,
        CreditmemoService $creditmemoService,
        Calculation $calculationTool,
        LoggerInterface $logger,
        Item $taxItem,
        Json $serializer
    ) {
        parent::__construct($transactionFactory, $creditMemoHelper, $creditMemoFactory);
        $this->creditmemoService = $creditmemoService;
        $this->calculationTool = $calculationTool;
        $this->logger = $logger;
        $this->taxItem = $taxItem;
        $this->serializer = $serializer;
    }

    /**
     * @param Order $magentoOrder
     * @param ShopOrderLine $miraklOrderLine
     * @param MiraklRefund $refund
     * @param Invoice $invoiceobj
     * @return  CreditMemo|null
     * @throws  Exception
     */
    public function create(Order $magentoOrder, ShopOrderLine $miraklOrderLine, MiraklRefund $refund, $invoiceobj = null)
    {
        if (!$magentoOrder->canCreditmemo()) {
            throw new Exception('Cannot create credit memo for the order.');
        }

        $existingCreditMemo = $this->creditMemoHelper->getCreditMemoByMiraklRefundId($refund->getId());
        if ($existingCreditMemo->getId()) {
            return null;
        }

        $orderItem = $this->getOrderItemBySku($magentoOrder, $miraklOrderLine->getOffer()->getProduct()->getSku());

        if (!$orderItem) {
            return null;
        }

        $setZeroItemQty = false;
        if (!$refund->getQuantity() && $refund->getAmount()) {
            // Set quantity to 1 temporarily to allow credit memo item creation
            $refund->setQuantity(1);
            $setZeroItemQty = true;
        }

        $creditMemoData = [
            'qtys' => [$orderItem->getId() => $refund->getQuantity()],
        ];

        $creditMemo = $this->creditMemoFactory->createByOrder($magentoOrder, $creditMemoData);
        if ($invoiceobj) {
            $creditMemo->setInvoice($invoiceobj);
        }

        $creditMemoItem = null;
        /** @var CreditmemoItemInterface $creditMemoItem */
        foreach ($creditMemo->getItems() as $k => $item) {
            if ($item->getSku() === $miraklOrderLine->getOffer()->getProduct()->getSku()) {
                $creditMemoItem = $item; // Retrieve credit memo item associated to Mirakl offer sku
            }
        }

        if (!$creditMemoItem) {
            return null;
        }

        $itemTax = 0;

        $creditMemoItem->setTaxAmount($itemTax);

        $maxRefund = $orderItem->getBaseRowTotal() - $orderItem->getBaseDiscountAmount() - $orderItem->getBaseAmountRefunded();
        $refundAmount = min($refund->getAmount(), $maxRefund);
        $maxShippingRefund = $magentoOrder->getBaseShippingAmount() - $magentoOrder->getBaseShippingDiscountAmount() - $magentoOrder->getBaseShippingRefunded();
        $shippingRefundAmount = min($refund->getShippingAmount(), $maxShippingRefund);

        if ($refund->getQuantity()) {
            $creditMemoItem->setBasePrice($refundAmount / $refund->getQuantity());
            $creditMemoItem->setPrice($refundAmount / $refund->getQuantity());
            $creditMemoItem->setBasePriceInclTax($creditMemoItem->getBasePrice() + ($itemTax / $refund->getQuantity()));
            $creditMemoItem->setPriceInclTax($creditMemoItem->getPrice() + ($itemTax / $refund->getQuantity()));
        } else {
            $creditMemoItem->setBasePrice($refundAmount);
            $creditMemoItem->setPrice($refundAmount);
            $creditMemoItem->setBasePriceInclTax($creditMemoItem->getBasePrice() + $itemTax);
            $creditMemoItem->setPriceInclTax($creditMemoItem->getPrice() + $itemTax);
        }

        $creditMemoItem->setBaseRowTotal($refundAmount);
        $creditMemoItem->setRowTotal($refundAmount);
        $creditMemoItem->setBaseRowTotalInclTax($refundAmount + $itemTax);
        $creditMemoItem->setRowTotalInclTax($refundAmount + $itemTax);

        if ($setZeroItemQty) {
            $creditMemoItem->setQty(0);
        }

        $shippingTax = 0;

        // Shipping tax amount
        $creditMemo->setBaseShippingTaxAmount($shippingTax);
        $creditMemo->setShippingTaxAmount($shippingTax);

        // Shipping amount excluding tax
        $creditMemo->setBaseShippingAmount($shippingRefundAmount);
        $creditMemo->setShippingAmount($shippingRefundAmount);

        // Shipping amount including tax
        $creditMemo->setBaseShippingInclTax($shippingRefundAmount + $shippingTax);
        $creditMemo->setShippingInclTax($shippingRefundAmount + $shippingTax);

        // Subtotal amount excluding tax
        $creditMemo->setBaseSubtotal($creditMemoItem->getBaseRowTotal());
        $creditMemo->setSubtotal($creditMemoItem->getRowTotal());

        // Subtotal amount including tax
        $creditMemo->setBaseSubtotalInclTax($creditMemoItem->getBaseRowTotalInclTax());
        $creditMemo->setSubtotalInclTax($creditMemoItem->getRowTotalInclTax());

        // Grand total including tax
        $creditMemo->setBaseGrandTotal($creditMemo->getBaseSubtotalInclTax() + $creditMemo->getBaseShippingInclTax());
        $creditMemo->setGrandTotal($creditMemo->getSubtotalInclTax() + $creditMemo->getShippingInclTax());

        // Total tax amount
        $creditMemo->setBaseTaxAmount($itemTax + $shippingTax);
        $creditMemo->setTaxAmount($itemTax + $shippingTax);

        // Credit memo state
        if ($refund->getState() === MiraklRefund\RefundState::REFUNDED) {
            $creditMemo->setState(CreditMemo::STATE_REFUNDED);
        } else {
            $creditMemo->setState(CreditMemo::STATE_OPEN);
        }

        // Save Mirakl refund id on the credit memo to mark it as imported
        $creditMemo->setMiraklRefundId($refund->getId());
        $creditMemo->setMiraklRefundTaxes(json_encode($refund->getTaxes()->toArray()));
        $creditMemo->setMiraklRefundShippingTaxes(json_encode($refund->getShippingTaxes()->toArray()));
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/refund-error.log');
        $logger2 = new \Zend\Log\Logger();
        $logger2->addWriter($writer);
        try {
            $this->creditmemoService->refund($creditMemo);
        } catch (Exception $e) {
            $logger2->info("Exception : " . $e->getMessage());
            $logger2->info("Exception Trace : " . $e->getTraceAsString());
        }

        return $creditMemo;
    }

    /**
     * Create Combined
     *
     * @param Order $magentoOrder
     * @param ShopOrderLine[] $miraklOrderLines
     * @param MiraklRefund[] $refunds
     * @param Invoice|null $invoiceobj
     * @return CreditMemo
     * @throws Exception
     */
    public function createCombined(
        Order $magentoOrder,
        array $miraklOrderLines,
        array $refunds,
        Invoice $invoiceobj = null
    ): CreditMemo {
        if (!$magentoOrder->canCreditmemo()) {
            throw new Exception('Cannot create credit memo for the order.');
        }

        $miraklOrderLinesRefunds = $this->getMiraklOrderLineRefunds($miraklOrderLines, $refunds);
        $creditMemoData = $this->getCreditMemoData($miraklOrderLines, $refunds, $magentoOrder);
        $creditMemo = $this->creditMemoFactory->createByOrder($magentoOrder, $creditMemoData);
        $creditMemo->setState(CreditMemo::STATE_OPEN);
        if ($invoiceobj) {
            $creditMemo->setInvoice($invoiceobj);
        }
        $maxBaseOrderShippingRefund = $magentoOrder->getBaseShippingInclTax()
            - $magentoOrder->getBaseShippingDiscountAmount()
            - $magentoOrder->getBaseShippingRefunded()
            - $magentoOrder->getBaseShippingTaxRefunded();
        $baseShippingTaxAmount = 0;
        $shippingTaxAmount = 0;
        $baseShippingAmount = 0;
        $baseSubtotal = 0;
        $subtotal = 0;
        $baseSubtotalInclTax = 0;
        $subtotalInclTax = 0;
        $allItemsTax = 0;
        $appliedRefunds = [];

        foreach ($miraklOrderLines as $miraklOrderLine) {
            /** @var CreditmemoItemInterface $creditMemoItem */
            foreach ($creditMemo->getItems() as $item) {
                if ($item->getSku() === $miraklOrderLine->getOffer()->getProduct()->getSku()) {
                    $creditMemoItem = $item; // Retrieve credit memo item associated to Mirakl offer sku
                } else {
                    continue;
                }

                $itemTax = 0;
                $creditMemoItem->setTaxAmount($itemTax);

                $parent = $magentoOrder->getItemById($creditMemoItem->getOrderItemId())->getParentItem();
                if ($parent && $parent->getProductType() == Configurable::TYPE_CODE) {
                    continue;
                }

                $orderItem = $this->getOrderItemBySku($magentoOrder, $miraklOrderLine->getOffer()->getProduct()->getSku());
                $maxBaseOrderItemRefundAmount = $orderItem->getBaseRowTotalInclTax()
                    - $orderItem->getBaseDiscountAmount()
                    - $orderItem->getBaseAmountRefunded()
                    - $orderItem->getBaseTaxRefunded();
                $maxBaseOrderItemTaxRefundAmount = $orderItem->getBaseTaxAmount() - $orderItem->getBaseTaxRefunded();

                $itemRate = $this->getTaxItemRate($magentoOrder, $orderItem);
                $orderLineRefundData = $this->combineRefundsForOrderLine(
                    $creditMemo,
                    $miraklOrderLinesRefunds[$miraklOrderLine->getId()],
                    $maxBaseOrderItemRefundAmount,
                    $maxBaseOrderItemTaxRefundAmount,
                    $itemRate
                );
                $appliedRefunds = array_merge($appliedRefunds, $orderLineRefundData['appliedRefunds']);

                if (!$orderLineRefundData['refundItemQty']) {
                    $creditMemoItem->setQty(0);
                }
                $creditMemoItem->setTaxAmount($orderLineRefundData['itemTaxAmount']);
                $creditMemoItem->setBaseTaxAmount($orderLineRefundData['itemTaxAmount']);
                $creditMemoItem->setBasePrice($orderLineRefundData['itemBasePrice']);
                $creditMemoItem->setPrice($orderLineRefundData['itemPrice']);
                $creditMemoItem->setBasePriceInclTax($orderLineRefundData['itemBasePriceInclTax']);
                $creditMemoItem->setPriceInclTax($orderLineRefundData['itemPriceInclTax']);
                $creditMemoItem->setBaseRowTotal($orderLineRefundData['itemBaseRowTotal']);
                $creditMemoItem->setRowTotal($orderLineRefundData['itemRowTotal']);
                $creditMemoItem->setBaseRowTotalInclTax($orderLineRefundData['itemBaseRowTotalInclTax']);
                $creditMemoItem->setRowTotalInclTax($orderLineRefundData['itemRowTotalInclTax']);

                $baseSubtotal += $creditMemoItem->getBaseRowTotal();
                $subtotal += $creditMemoItem->getRowTotal();
                $baseSubtotalInclTax += $creditMemoItem->getBaseRowTotalInclTax();
                $subtotalInclTax += $creditMemoItem->getBaseRowTotalInclTax();
                $baseShippingAmount += $orderLineRefundData['orderLineShippingRefundAmount'];
                $allItemsTax += $orderLineRefundData['itemTaxAmount'];
            }
        }
        $baseShippingAmount = min($baseShippingAmount, $maxBaseOrderShippingRefund);

        $shippingRate = $this->getTaxItemRate($magentoOrder, null, 'shipping');
        if (!empty($shippingRate) && $shippingRate > 0) {
            $rowShippingTaxExact = $this->calculationTool->calcTaxAmount($baseShippingAmount, $shippingRate, true, false);
            $baseShippingTaxAmount = $this->calculationTool->round($rowShippingTaxExact);
            $shippingTaxAmount = $baseShippingTaxAmount;
            $baseShippingAmount = $baseShippingAmount - $baseShippingTaxAmount;
        }

        $shippingAmount = $baseShippingAmount;

        $baseShippingInclTax = $baseShippingAmount + $baseShippingTaxAmount;
        $shippingInclTax = $shippingAmount + $shippingTaxAmount;
        $baseTaxAmount = $allItemsTax + $shippingTaxAmount;
        $taxAmount = $allItemsTax + $shippingTaxAmount;
        $baseGrandTotal = $baseSubtotalInclTax + $baseShippingInclTax;
        $grandTotal = $subtotalInclTax + $shippingInclTax;

        $creditMemo->setBaseShippingTaxAmount($baseShippingTaxAmount);
        $creditMemo->setShippingTaxAmount($shippingTaxAmount);

        // Shipping amount excluding tax
        $creditMemo->setBaseShippingAmount($baseShippingAmount);
        $creditMemo->setShippingAmount($shippingAmount);

        // Shipping amount including tax
        $creditMemo->setBaseShippingInclTax($baseShippingInclTax);
        $creditMemo->setShippingInclTax($shippingInclTax);

        // Subtotal amount excluding tax
        $creditMemo->setBaseSubtotal($baseSubtotal);
        $creditMemo->setSubtotal($subtotal);

        // Subtotal amount including tax
        $creditMemo->setBaseSubtotalInclTax($baseSubtotalInclTax);
        $creditMemo->setSubtotalInclTax($subtotalInclTax);

        // Total tax amount
        $creditMemo->setBaseTaxAmount($baseTaxAmount);
        $creditMemo->setTaxAmount($taxAmount);

        $creditMemo->setBaseGrandTotal($baseGrandTotal);
        $creditMemo->setGrandTotal($grandTotal);
        $creditMemo->setMiraklRefundIds(implode(',', $appliedRefunds));

        try {
            $this->creditmemoService->refund($creditMemo);
        } catch (Exception $e) {
            $this->logger->info("Exception : " . $e->getMessage());
            $this->logger->info("Exception Trace : " . $e->getTraceAsString());
        }

        return $creditMemo;
    }

    /**
     * Get Order Item By Sku
     *
     * @param Order $magentoOrder
     * @param string $sku
     * @return  OrderItem|null
     */
    private function getOrderItemBySku(Order $magentoOrder, string $sku): ?OrderItem
    {
        /** @var OrderItem $orderItem */
        foreach ($magentoOrder->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getSku() === $sku) {
                return $orderItem;
            }
        }

        return null;
    }

    /**
     * Get Tax Item Rate
     *
     * @param Order $order
     * @param OrderItem|null $item
     * @param string|null $type
     * @return float
     */
    private function getTaxItemRate(Order $order, ?OrderItem $item, ?string $type = null): float
    {
        if ($this->taxItems === null) {
            $this->taxItems = $this->taxItem->getTaxItemsByOrderId($order->getId());
        }

        foreach ($this->taxItems as $taxItem) {
            if (!empty($item)) {
                $parentItem = $item->getParentItem();
                $itemId = !empty($parentItem) ? $parentItem->getItemId() : $item->getItemId();
                if ($itemId == $taxItem['item_id']) {
                    return (float) $taxItem['tax_percent'];
                }
            }

            if (!empty($type) && $type == $taxItem['taxable_item_type']) {
                return (float) $taxItem['tax_percent'];
            }
        }

        return 0;
    }

    /**
     * Get grouped refunds array by order line
     *
     * @param array $miraklOrderLines
     * @param array $refunds
     * @return array
     */
    private function getMiraklOrderLineRefunds($miraklOrderLines, $refunds): array
    {
        $miraklOrderLinesRefunds = [];
        foreach ($miraklOrderLines as $miraklOrderLine) {
            foreach ($miraklOrderLine->getRefunds() as $refund) {
                if (!isset($refunds[$refund->getId()])) {
                    continue;
                }
                $miraklOrderLinesRefunds[$miraklOrderLine->getId()][] = $refund;
            }
        }

        return $miraklOrderLinesRefunds;
    }

    /**
     * Get order line qty data from refunds
     *
     * @param array $miraklOrderLines
     * @param array $refunds
     * @param Order $magentoOrder
     * @return array
     */
    private function getCreditMemoData($miraklOrderLines, $refunds, $magentoOrder): array
    {
        $creditMemoData = [];
        foreach ($miraklOrderLines as $miraklOrderLine) {
            $orderItem = $this->getOrderItemBySku($magentoOrder, $miraklOrderLine->getOffer()->getProduct()->getSku());
            if (!$orderItem) {
                continue;
            }
            $orderItemId = $orderItem->getId();
            $refundsAmount = 0;
            foreach ($miraklOrderLine->getRefunds() as $refund) {
                if (!isset($refunds[$refund->getId()])) {
                    continue;
                }
                $refundsAmount += $refund->getAmount();
                $qty = $refund->getQuantity();
                if (isset($creditMemoData['qtys'][$orderItemId])) {
                    $creditMemoData['qtys'][$orderItemId] += $qty;
                } else {
                    $creditMemoData['qtys'][$orderItemId] = $qty;
                }
            }
            //to create creditMemoItem if refundItemQty = 0
            if (!$creditMemoData['qtys'][$orderItemId] && $refundsAmount) {
                $creditMemoData['qtys'][$orderItemId] = 1;
            }
        }

        return $creditMemoData;
    }

    /**
     * Combine data from different refunds for an order line item
     *
     * @param CreditMemo $creditMemo
     * @param array $orderLineRefunds
     * @param double $maxBaseOrderItemRefundAmount
     * @param double $maxBaseOrderItemTaxRefundAmount
     * @param double $itemRate
     * @return array
     */
    private function combineRefundsForOrderLine(
        $creditMemo,
        $orderLineRefunds,
        $maxBaseOrderItemRefundAmount,
        $maxBaseOrderItemTaxRefundAmount,
        $itemRate
    ) {
        $orderLineShippingRefundAmount = 0;
        $itemTaxAmount = 0;
        $itemBaseRowTotalInclTax = 0;
        $refundItemQty = 0;

        foreach ($orderLineRefunds as $refund) {
            $refundItemQty += $refund->getQuantity();
            $orderLineShippingRefundAmount += $refund->getShippingAmount();
            $itemBaseRowTotalInclTax += $refund->getAmount();

            // Credit memo state
            if ($refund->getState() === MiraklRefund\RefundState::REFUNDED) {
                $creditMemo->setState(CreditMemo::STATE_REFUNDED);
            }
            $appliedRefunds[] = $refund->getId();
        }
        $itemBaseRowTotalInclTax = min($maxBaseOrderItemRefundAmount, $itemBaseRowTotalInclTax);
        //Calculate taxes for the refunded order line
        if (!empty($itemRate) && $itemRate > 0) {
            $rowTaxExact = $this->calculationTool->calcTaxAmount(
                $itemBaseRowTotalInclTax,
                $itemRate,
                true,
                false
            );
            $itemTaxAmount = $this->calculationTool->round($rowTaxExact);
            $itemTaxAmount = min($itemTaxAmount, $maxBaseOrderItemTaxRefundAmount);
            if ($maxBaseOrderItemRefundAmount == $itemBaseRowTotalInclTax) {
                $itemTaxAmount = $maxBaseOrderItemTaxRefundAmount;
            }
        }

        $itemRowTotalInclTax = $itemBaseRowTotalInclTax;
        $itemBaseRowTotal = $itemBaseRowTotalInclTax - $itemTaxAmount;
        $itemRowTotal = $itemRowTotalInclTax - $itemTaxAmount;

        $itemBasePrice = $itemBaseRowTotal / ($refundItemQty ?: 1);
        $itemPrice = $itemRowTotal / ($refundItemQty ?: 1);
        $itemBasePriceInclTax = $itemBaseRowTotalInclTax / ($refundItemQty ?: 1);
        $itemPriceInclTax = $itemRowTotalInclTax / ($refundItemQty ?: 1);

        return [
            'refundItemQty' => $refundItemQty,
            'orderLineShippingRefundAmount' => $orderLineShippingRefundAmount,
            'itemTaxAmount' => $itemTaxAmount,
            'itemRowTotalInclTax' => $itemRowTotalInclTax,
            'itemBasePrice' => $itemBasePrice,
            'itemPrice' => $itemPrice,
            'itemBaseRowTotal' => $itemBaseRowTotal,
            'itemBaseRowTotalInclTax' => $itemBaseRowTotalInclTax,
            'itemRowTotal' => $itemRowTotal,
            'itemBasePriceInclTax' => $itemBasePriceInclTax,
            'itemPriceInclTax' => $itemPriceInclTax,
            'appliedRefunds' => $appliedRefunds
        ];
    }
}
