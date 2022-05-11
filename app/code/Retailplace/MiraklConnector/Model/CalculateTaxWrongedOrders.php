<?php
/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Psr\Log\LoggerInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Framework\Exception\InputException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Exception;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class CalculateTaxWrongedOrders
 */
class CalculateTaxWrongedOrders
{
    /**
     * @var int
     */
    const TAX_PERCENT = 10;

    /**
     * @var string
     */
    const ATTRIBUTE_CODE_GST_EXEMPT = 'gst_exempt';

    /**
     * @var string
     */
    const ATTRIBUTE_CODE_TAX_CLASS = 'tax_class_id';

    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollection;

    /**
     * @var TaxClassManagementInterface
     */
    private $taxClassManagementInterface;

    /**
     * @var TaxClassKeyInterfaceFactory
     */
    private $taxClassKeyDataObjectFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $attributeGstExemptOptionId;

    /**
     * @var int|null
     */
    private $taxClassId;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var ItemRepository
     */
    private $orderItemRepository;

    /**
     * @var string
     */
    private $logFileName;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * AuPostAttributeUpdater constructor.
     * @param AttributeRepositoryInterface $attributeRepository
     * @param OrderItemCollectionFactory $collection
     * @param OrderRepository $orderRepository
     * @param ItemRepository $orderItemRepository
     * @param TaxClassManagementInterface $taxClassManagementInterface
     * @param TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        OrderItemCollectionFactory   $collection,
        OrderRepository              $orderRepository,
        ItemRepository               $orderItemRepository,
        TaxClassManagementInterface  $taxClassManagementInterface,
        TaxClassKeyInterfaceFactory  $taxClassKeyDataObjectFactory,
        LoggerInterface              $logger,
        ResourceConnection           $resource
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->orderItemCollection = $collection;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->taxClassManagementInterface = $taxClassManagementInterface;
        $this->taxClassKeyDataObjectFactory = $taxClassKeyDataObjectFactory;
        $this->logger = $logger;
        $this->resource = $resource;
        $this->logFileName = BP . '/var/log/' . date('Ymd_His') . '_calculateTaxWrongedOrders.log';
    }

    /**
     * Update Attribute Values for Products
     * @param bool|null $isSoftRun
     * @param array|null $ids
     */
    public function update(?array $ids = [], bool $isSoftRun = false)
    {
        $updateOrders = $this->getWrongedOrders($ids);

        foreach ($updateOrders as $orderId => $orderItems) {
            try {
                if ($isSoftRun) {
                    echo $orderId . "\r\n";
                } else {
                    $this->updateOrder($orderId, $orderItems);
                }
            } catch (Exception | InputException | NoSuchEntityException $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     * Get Parent Item
     *
     * @param $id
     * @return OrderItemInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getParentItem($id): OrderItemInterface
    {
        return $this->orderItemRepository->get($id);
    }

    /**
     * Update Order
     *
     * @param string|int $orderId
     * @param array $orderItems
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function updateOrder($orderId, array $orderItems)
    {
        $order = $this->orderRepository->get($orderId);
        if (!$order) {
            return;
        }

        $taxId = $this->getTaxId($order->getEntityId());
        $allItemIds = [];

        $this->getConnection()->beginTransaction(); //start transaction

        try {
            foreach ($orderItems as $orderItem) {
                $parentItemId = $orderItem->getParentItemId();
                if ($parentItemId) {
                    $parentOrderItem = $this->getParentItem($parentItemId);
                    $item = $parentOrderItem;
                } else {
                    $item = $orderItem;
                }

                if (!$item->getRowTotal()) {
                    continue;
                }

                $discountTaxCompensationAmount = $this->calcTaxAmount($item->getDiscountAmount(), true);
                $baseDiscountTaxCompensationAmount = $this->calcTaxAmount($item->getBaseDiscountAmount(), true);
                $discountTaxCompensationRefundedAmount = $this->calcTaxAmount($item->getDiscountRefunded());
                $baseDiscountTaxCompensationRefundedAmount = $this->calcTaxAmount($item->getBaseDiscountRefunded());

                $dataItem = [
                    'tax_percent' => self::TAX_PERCENT,
                    'price' => $this->getAmountWithoutTax($item->getPriceInclTax(), true),
                    'base_price' => $this->getAmountWithoutTax($item->getBasePriceInclTax(), true),
                    'tax_amount' => round($this->calcTaxAmount($item->getRowTotalInclTax()) - $discountTaxCompensationAmount, 2),
                    'base_tax_amount' => round($this->calcTaxAmount($item->getBaseRowTotalInclTax()) - $baseDiscountTaxCompensationAmount, 2),
                    'row_total' => $this->getAmountWithoutTax($item->getRowTotal(), true),
                    'base_row_total' => $this->getAmountWithoutTax($item->getBaseRowTotal(), true),
                    'tax_invoiced' => round($this->calcTaxAmount($item->getRowTotalInclTax()) - $discountTaxCompensationAmount, 2),
                    'base_tax_invoiced' => round($this->calcTaxAmount($item->getBaseRowTotalInclTax()) - $baseDiscountTaxCompensationAmount, 2),
                    'row_invoiced' => $this->getAmountWithoutTax($item->getRowInvoiced(), true),
                    'base_row_invoiced' => $this->getAmountWithoutTax($item->getBaseRowInvoiced(), true),
                    'discount_tax_compensation_amount' => $discountTaxCompensationAmount,
                    'base_discount_tax_compensation_amount' => $baseDiscountTaxCompensationAmount,
                    'discount_tax_compensation_invoiced' => $discountTaxCompensationAmount,
                    'base_discount_tax_compensation_invoiced' => $baseDiscountTaxCompensationAmount,
                    'discount_tax_compensation_refunded' => $discountTaxCompensationRefundedAmount,
                    'base_discount_tax_compensation_refunded' => $baseDiscountTaxCompensationRefundedAmount,
                    'tax_refunded' => $this->calcTaxAmount($item->getAmountRefunded(), true),
                    'base_tax_refunded' => $this->calcTaxAmount($item->getBaseAmountRefunded(), true),
                    'amount_refunded' => $this->getAmountWithoutTax($item->getAmountRefunded(), true),
                    'base_amount_refunded' => $this->getAmountWithoutTax($item->getBaseAmountRefunded(), true)
                ];

                $dataItem = $this->truncateArray($dataItem);

                /**
                 * step_1 update sales_order_item
                 */
                $this->logging($orderId, 'order_item', $item->getData(), $dataItem);
                $this->getConnection()->update($this->getTable('sales_order_item'), $dataItem, ["item_id = ?" => $item->getItemId()]);
                $order->getItemById($item->getItemId())->addData($dataItem);
                $allItemIds[$item->getItemId()] = $item->getItemId();

                /**
                 * step_2 update sales_order_tax_item
                 */
                $taxItemId = $this->getItemTaxId($item->getItemId()); //sales_order_tax_item

                $dataItemTax = [
                    'amount' => $dataItem['tax_amount'],
                    'base_amount' => $dataItem['base_tax_amount'],
                    'real_amount' => $dataItem['tax_amount'],
                    'real_base_amount' => $dataItem['base_tax_amount']
                ];

                if (!$taxItemId) {
                    $dataItemTax['tax_id'] = $taxId;
                    $dataItemTax['item_id'] = $item->getItemId();
                    $dataItemTax['tax_percent'] = self::TAX_PERCENT;
                    $dataItemTax['taxable_item_type'] = 'product';
                    $this->getConnection()->insert($this->getTable('sales_order_tax_item'), $dataItemTax);
                } else {
                    $this->getConnection()->update(
                        $this->getTable('sales_order_tax_item'),
                        $dataItemTax,
                        ["tax_item_id = ?" => $taxItemId]
                    );
                }
            }

            /**
             * step_3 update sales_order
             */
            $dataOrder = [
                'tax_amount' => $order->getShippingTaxAmount(),
                'base_tax_amount' => $order->getBaseShippingTaxAmount(),
                'tax_invoiced' => $order->getShippingTaxAmount(),
                'base_tax_invoiced' => $order->getBaseShippingTaxAmount(),
                'tax_refunded' => $order->getShippingTaxRefunded(),
                'base_tax_refunded' => $order->getBaseShippingTaxRefunded(),
                'discount_tax_compensation_refunded' => $this->calcTaxAmount(abs($order->getDiscountRefunded())),
                'base_discount_tax_compensation_refunded' => $this->calcTaxAmount(abs($order->getBaseDiscountRefunded())),
            ];

            foreach ($order->getItems() as $item) {
                $this->calcValueInArray($dataOrder, 'subtotal', $item->getRowTotal());
                $this->calcValueInArray($dataOrder, 'base_subtotal', $item->getBaseRowTotal());
                $this->calcValueInArray($dataOrder, 'subtotal_refunded', $item->getAmountRefunded());
                $this->calcValueInArray($dataOrder, 'base_subtotal_refunded', $item->getBaseAmountRefunded());
                $this->calcValueInArray($dataOrder, 'subtotal_invoiced', $item->getRowInvoiced());
                $this->calcValueInArray($dataOrder, 'base_subtotal_invoiced', $item->getBaseRowInvoiced());
                $this->calcValueInArray($dataOrder, 'tax_amount', $item->getTaxAmount());
                $this->calcValueInArray($dataOrder, 'base_tax_amount', $item->getBaseTaxAmount());
                $this->calcValueInArray($dataOrder, 'tax_invoiced', $item->getTaxInvoiced());
                $this->calcValueInArray($dataOrder, 'base_tax_invoiced', $item->getBaseTaxInvoiced());
                $this->calcValueInArray($dataOrder, 'tax_canceled', $item->getTaxCanceled());
                $this->calcValueInArray($dataOrder, 'base_tax_canceled', $item->getBaseTaxCanceled());
                $this->calcValueInArray($dataOrder, 'discount_tax_compensation_amount', $item->getData('discount_tax_compensation_amount'));
                $this->calcValueInArray($dataOrder, 'discount_tax_compensation_invoiced', $item->getData('discount_tax_compensation_invoiced'));
                $this->calcValueInArray($dataOrder, 'base_discount_tax_compensation_amount', $item->getData('base_discount_tax_compensation_amount'));
                $this->calcValueInArray($dataOrder, 'base_discount_tax_compensation_invoiced', $item->getData('base_discount_tax_compensation_invoiced'));
                $this->calcValueInArray($dataOrder, 'tax_refunded', $item->getData('tax_refunded'));
                $this->calcValueInArray($dataOrder, 'base_tax_refunded', $item->getData('base_tax_refunded'));
            }

            $this->logging($orderId, 'order', $order->getData(), $dataOrder);
            $this->getConnection()->update(
                $this->getTable('sales_order'),
                $dataOrder,
                ["entity_id = ?" => $order->getEntityId()]
            );


            /**
             * step_4 update sales_order_grid
             */
            $this->getConnection()->update(
                $this->getTable('sales_order_grid'),
                [
                    'subtotal' => $dataOrder['subtotal']
                ],
                ["entity_id = ?" => $order->getEntityId()]
            );

            /**
             * step_5 update sales_order_tax
             */
            $dataTax = [
                'amount' => $dataOrder['tax_amount'],
                'base_amount' => $dataOrder['base_tax_amount'],
                'base_real_amount' => $dataOrder['base_tax_amount']
            ];

            $this->getConnection()->update(
                $this->getTable('sales_order_tax'),
                $dataTax,
                ["tax_id = ?" => $taxId]
            );

            foreach ($order->getInvoiceCollection() as $invoice) {
                /**
                 * step_6 update sales_invoice_item
                 */
                foreach ($invoice->getAllItems() as $invoiceItem) {
                    if (!array_key_exists($invoiceItem->getOrderItemId(), $allItemIds)) {
                        continue;
                    }

                    $discountTaxCompensationAmount = $this->calcTaxAmount($invoiceItem->getDiscountAmount(), true);
                    $baseDiscountTaxCompensationAmount = $this->calcTaxAmount($invoiceItem->getBaseDiscountAmount(), true);

                    $invoiceItemData = [
                        'price' => $this->getAmountWithoutTax($invoiceItem->getPriceInclTax(), true),
                        'base_price' => $this->getAmountWithoutTax($invoiceItem->getBasePriceInclTax(), true),
                        'tax_amount' => round($this->calcTaxAmount($invoiceItem->getRowTotalInclTax()) - $discountTaxCompensationAmount, 2),
                        'base_tax_amount' => round($this->calcTaxAmount($invoiceItem->getBaseRowTotalInclTax()) - $baseDiscountTaxCompensationAmount, 2),
                        'row_total' => $this->getAmountWithoutTax($invoiceItem->getRowTotal(), true),
                        'base_row_total' => $this->getAmountWithoutTax($invoiceItem->getBaseRowTotal(), true),
                        'discount_tax_compensation_amount' => $discountTaxCompensationAmount,
                        'base_discount_tax_compensation_amount' => $baseDiscountTaxCompensationAmount,
                    ];

                    $invoiceItemData = $this->truncateArray($invoiceItemData);

                    $this->logging($orderId, 'invoice_item', $invoiceItem->getData(), $invoiceItemData);
                    $this->getConnection()->update(
                        $this->getTable('sales_invoice_item'),
                        $invoiceItemData,
                        ["entity_id = ?" => $invoiceItem->getEntityId()]
                    );
                    $invoiceItem->addData($invoiceItemData);
                }

                /**
                 * step_7 update sales_invoice
                 */
                $invoiceData = [
                    'tax_amount' => $invoice->getShippingTaxAmount(),
                    'base_tax_amount' => $invoice->getBaseShippingTaxAmount(),
                ];

                foreach ($invoice->getAllItems() as $invoiceItem) {
                    $this->calcValueInArray($invoiceData, 'subtotal', $invoiceItem->getRowTotal());
                    $this->calcValueInArray($invoiceData, 'base_subtotal', $invoiceItem->getBaseRowTotal());
                    $this->calcValueInArray($invoiceData, 'tax_amount', $invoiceItem->getTaxAmount());
                    $this->calcValueInArray($invoiceData, 'base_tax_amount', $invoiceItem->getBaseTaxAmount());
                    $this->calcValueInArray($invoiceData, 'discount_tax_compensation_amount', $invoiceItem->getData('discount_tax_compensation_amount'));
                    $this->calcValueInArray($invoiceData, 'base_discount_tax_compensation_amount', $invoiceItem->getData('base_discount_tax_compensation_amount'));
                }

                $this->logging($orderId, 'invoice', $invoice->getData(), $invoiceData);
                $this->getConnection()->update(
                    $this->getTable('sales_invoice'),
                    $invoiceData,
                    ["entity_id = ?" => $invoice->getEntityId()]
                );

                /**
                 * step_8 update sales_invoice_grid
                 */
                $this->getConnection()->update(
                    $this->getTable('sales_invoice_grid'),
                    [
                        'subtotal' => $invoiceData['subtotal'],
                    ],
                    ["entity_id = ?" => $invoice->getEntityId()]
                );
            }

            $creditmemoCollections = $order->getCreditmemosCollection();

            foreach ($creditmemoCollections as $creditmemo) {
                /**
                 * step_9 update sales_creditmemo_item
                 */
                foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                    if (!array_key_exists($creditmemoItem->getOrderItemId(), $allItemIds)) {
                        continue;
                    }

                    $discountTaxCompensationAmount = $this->calcTaxAmount($creditmemoItem->getDiscountAmount(), true);
                    $baseDiscountTaxCompensationAmount = $this->calcTaxAmount($creditmemoItem->getBaseDiscountAmount(), true);

                    $creditmemoItemData = [
                        'price' => $this->getAmountWithoutTax($creditmemoItem->getPriceInclTax(), true),
                        'base_price' => $this->getAmountWithoutTax($creditmemoItem->getBasePriceInclTax(), true),
                        'tax_amount' => $this->calcTaxAmount($creditmemoItem->getRowTotalInclTax(), true),
                        'base_tax_amount' => $this->calcTaxAmount($creditmemoItem->getBaseRowTotalInclTax(), true),
                        'row_total' => $this->getAmountWithoutTax($creditmemoItem->getRowTotal()),
                        'base_row_total' => $this->getAmountWithoutTax($creditmemoItem->getBaseRowTotal()),
                        'discount_tax_compensation_amount' => $discountTaxCompensationAmount,
                        'base_discount_tax_compensation_amount' => $baseDiscountTaxCompensationAmount,
                    ];

                    $creditmemoItemData = $this->truncateArray($creditmemoItemData);

                    $this->logging($orderId, 'creditmemo_item', $creditmemoItem->getData(), $creditmemoItemData);
                    $this->getConnection()->update(
                        $this->getTable('sales_creditmemo_item'),
                        $creditmemoItemData,
                        ["entity_id = ?" => $creditmemoItem->getEntityId()]
                    );
                    $creditmemoItem->addData($creditmemoItemData);

                    /**
                     * step_10 update sales_creditmemo
                     */
                    $creditmemoData = [
                        'tax_amount' => $creditmemo->getShippingTaxAmount(),
                        'base_tax_amount' => $creditmemo->getBaseShippingTaxAmount(),
                        'discount_tax_compensation_amount' => $this->calcTaxAmount(abs($creditmemo->getDiscountAmount())),
                        'base_discount_tax_compensation_amount' => $this->calcTaxAmount(abs($creditmemo->getBaseDiscountAmount())),
                    ];

                    foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                        $this->calcValueInArray($creditmemoData, 'subtotal', $creditmemoItem->getRowTotal());
                        $this->calcValueInArray($creditmemoData, 'base_subtotal', $creditmemoItem->getBaseRowTotal());
                        $this->calcValueInArray($creditmemoData, 'tax_amount', $creditmemoItem->getTaxAmount());
                        $this->calcValueInArray($creditmemoData, 'base_tax_amount', $creditmemoItem->getBaseTaxAmount());
                    }

                    $this->logging($orderId, 'creditmemo', $creditmemo->getData(), $creditmemoData);
                    $this->getConnection()->update(
                        $this->getTable('sales_creditmemo'),
                        $creditmemoData,
                        ["entity_id = ?" => $creditmemo->getEntityId()]
                    );

                    /**
                     * step_11 update sales_invoice_grid
                     */
                    $this->getConnection()->update(
                        $this->getTable('sales_creditmemo_grid'),
                        [
                            'subtotal' => $creditmemoData['subtotal']
                        ],
                        ["entity_id = ?" => $creditmemo->getEntityId()]
                    );
                }
            }
        } catch (Exception $e) {
            $this->getConnection()->rollBack(); //rollback changes
            throw new Exception($e->getMessage());
        }
        $this->getConnection()->commit(); //end transaction
    }

    /**
     * Calc Value In Array
     *
     * @param array $data
     * @param string $key
     * @param float|string|int $value
     */
    private function calcValueInArray(array &$data, string $key, $value)
    {
        if (!empty($data[$key])) {
            $data[$key] += (float)$value;
        } else {
            $data[$key] = (float)$value;
        }
    }

    /**
     * Get Amount Without Tax
     *
     * @param float|null|string $amount
     * @param bool|null $isRound
     * @return float
     */
    private function getAmountWithoutTax($amount, ?bool $isRound = null): float
    {
        $amount = (float)$amount;

        if (!$amount || $amount <= 0) {
            return 0.0;
        }

        $result = $amount - $this->calcTaxAmount($amount, $isRound);
        return $isRound ? round($result, 2) : $result;
    }

    /**
     * Calc Tax Amount
     *
     * @param float|null|string $amount
     * @param bool|null $isRound
     * @return float
     */
    private function calcTaxAmount($amount, ?bool $isRound = null): float
    {
        $amount = (float)$amount;

        if (!$amount || $amount <= 0) {
            return 0.0;
        }

        $result = (float)($amount * self::TAX_PERCENT / (100.0 + self::TAX_PERCENT));
        return $isRound ? round($result, 2) : $result;
    }

    /**
     * Get Wronged Orders
     *
     * @param array|null $ids
     * @return array
     */
    private function getWrongedOrders(?array $ids): array
    {
        $updateOrders = [];

        $attributeGstExemptOptionId = $this->getGstExemptOptionId();

        $attributeTax = $this->getAttributeByCode(self::ATTRIBUTE_CODE_TAX_CLASS);
        $attributeGST = $this->getAttributeByCode(self::ATTRIBUTE_CODE_GST_EXEMPT);

        $collection = $this->orderItemCollection->create();
        $select = $collection->getSelect();
        $select->joinInner(
            ['o' => 'sales_order'],
            "o.entity_id = main_table.order_id",
            []
        );

        $select->joinLeft(
            ['attr_tax' => 'catalog_product_entity_int'],
            "attr_tax.entity_id = main_table.product_id AND attr_tax.store_id = 0 AND attr_tax.attribute_id = "
            . $attributeTax->getAttributeId(),
            []
        );

        $select->joinLeft(
            ['attr_gst' => 'catalog_product_entity_int'],
            "attr_gst.entity_id = main_table.product_id AND attr_gst.store_id = 0 AND attr_gst.attribute_id = "
            . $attributeGST->getAttributeId(),
            []
        );

        $select->joinLeft(['oi_parent' => 'sales_order_item'], "oi_parent.item_id = main_table.parent_item_id", []);

        $select->columns([
            'new_tax_percent' => "IF(attr_gst.value is null OR attr_gst.value != $attributeGstExemptOptionId, 10, 0)"
        ]);

        $select->where("IF(oi_parent.item_id is not null, oi_parent.tax_percent, main_table.tax_percent) != IF(attr_gst.value is null OR attr_gst.value != $attributeGstExemptOptionId, 10, 0)");
        $select->where("IF(attr_gst.value is null OR attr_gst.value != $attributeGstExemptOptionId, 10, 0) > 0");
        $select->where("o.created_at >= '2021-10-28 00:00:00'");
        $select->where("main_table.parent_item_id is null");
        if ($ids) {
            $select->where('main_table.order_id in (?)', $ids);
        }

        foreach ($collection as $item) {
            $updateOrders[$item->getOrderId()][] = $item;
        }

        return $updateOrders;
    }

    /**
     * Get Attribute by Code
     *
     * @param string $attributeCode
     * @return AttributeInterface|null
     */
    private function getAttributeByCode(string $attributeCode): ?AttributeInterface
    {
        $attribute = null;
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                $attributeCode
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $attribute;
    }

    /**
     * Get Tax Class ID
     *
     * @param $className
     * @return string|null
     */
    public function getTaxClassId($className): ?string
    {
        if ($this->taxClassId !== null) {
            return $this->taxClassId;
        }

        return $this->taxClassId = $this->taxClassManagementInterface->getTaxClassId(
            $this->taxClassKeyDataObjectFactory->create()
                ->setType(TaxClassKeyInterface::TYPE_NAME)
                ->setValue($className)
        );
    }

    /**
     * Get Gst Exempt Option Id
     *
     * @return string|null
     */
    public function getGstExemptOptionId(): ?string
    {
        if ($this->attributeGstExemptOptionId !== null) {
            return $this->attributeGstExemptOptionId;
        }

        $attributeGstExempt = $this->getAttributeByCode(self::ATTRIBUTE_CODE_GST_EXEMPT);
        $attributeGstExemptOptionId = "";
        foreach ($attributeGstExempt->getOptions() as $option) {
            if ($option->getLabel() == 'Yes') {
                $attributeGstExemptOptionId = $option->getValue();
                break;
            }
        }

        return $this->attributeGstExemptOptionId = $attributeGstExemptOptionId;
    }

    /**
     * Logging
     *
     * @param string|int $orderId
     * @param string $name
     * @param mixed $before
     * @param mixed $after
     */
    private function logging($orderId, string $name, $before, $after)
    {
        try {
            $fp = fopen($this->logFileName, 'a');

            foreach ($after as $key => $value) {
                $beforeValue = !empty($before[$key]) ? $before[$key] : "";
                fputcsv($fp, [
                    $orderId,
                    $name,
                    $key,
                    $beforeValue,
                    $value
                ]);
            }

            fclose($fp);
        } catch (Exception $e) {
        }
    }

    /**
     * Get connection
     *
     * @return AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        if ($this->connection === null) {
            $this->connection = $this->resource->getConnection();
        }

        return $this->connection;
    }

    /**
     * Get table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable(string $tableName): string
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Get Tax Id
     *
     * @param $orderId
     * @return int
     */
    private function getTaxId($orderId): int
    {
        $select = $this->getConnection()
            ->select()
            ->from([$this->getTable('sales_order_tax')], 'tax_id')
            ->where('order_id = ?', $orderId);

        $taxId = $this->getConnection()->fetchOne($select);

        return (int)$taxId;
    }

    /**
     * Get Item Tax Id
     *
     * @param $itemId
     * @return int
     */
    private function getItemTaxId($itemId): int
    {
        $select = $this->getConnection()
            ->select()
            ->from([$this->getTable('sales_order_tax_item')], 'tax_item_id')
            ->where('item_id = ?', $itemId)
            ->where('taxable_item_type = "product"');

        $itemTaxId = $this->getConnection()->fetchOne($select);

        return (int)$itemTaxId;
    }

    /**
     * floor Array
     *
     * @param array $data
     * @return array
     */
    private function truncateArray(array $data): array
    {
        return array_map(function ($value) {
            $value = (string) $value;
            if (($p = strpos($value, '.')) !== false) {
                $value = substr($value, 0, $p + 3);
            }

            return floatval($value);
        }, $data);
    }
}
