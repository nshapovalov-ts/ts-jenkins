<?php
namespace Mirakl\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Tax\Api\OrderTaxManagementInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Tax\Model\ResourceModel\Sales\Order\TaxFactory as TaxOrderResourceFactory;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class Tax extends AbstractHelper
{
    /**
     * @var TaxConfig
     */
    protected $taxConfig;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var TaxOrderResourceFactory
     */
    protected $taxOrderResourceFactory;

    /**
     * @var OrderTaxManagementInterface
     */
    protected $orderTaxManagement;

    /**
     * @var bool
     */
    protected $isAdmin = false;

    /**
     * @var array
     */
    protected $taxRates = [];

    /**
     * @param   Context                     $context
     * @param   TaxConfig                   $taxConfig
     * @param   TaxCalculation              $taxCalculation
     * @param   TaxOrderResourceFactory     $taxOrderResourceFactory
     * @param   OrderTaxManagementInterface $orderTaxManagement
     * @param   bool                        $isAdmin
     */
    public function __construct(
        Context $context,
        TaxConfig $taxConfig,
        TaxCalculation $taxCalculation,
        TaxOrderResourceFactory $taxOrderResourceFactory,
        OrderTaxManagementInterface $orderTaxManagement,
        $isAdmin = false
    ) {
        parent::__construct($context);
        $this->taxConfig = $taxConfig;
        $this->taxCalculation = $taxCalculation;
        $this->taxOrderResourceFactory = $taxOrderResourceFactory;
        $this->orderTaxManagement = $orderTaxManagement;
        $this->isAdmin = $isAdmin;
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesFullSummary($store = null)
    {
        return $this->taxConfig->displaySalesFullSummary($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesPricesInclTax($store = null)
    {
        return $this->taxConfig->displaySalesPricesInclTax($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesPricesExclTax($store = null)
    {
        return $this->taxConfig->displaySalesPricesExclTax($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesPricesBoth($store = null)
    {
        return $this->taxConfig->displaySalesPricesBoth($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesShippingInclTax($store = null)
    {
        return $this->taxConfig->displaySalesShippingInclTax($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesShippingExclTax($store = null)
    {
        return $this->taxConfig->displaySalesShippingExclTax($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesShippingBoth($store = null)
    {
        return $this->taxConfig->displaySalesShippingBoth($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesSubtotalInclTax($store = null)
    {
        return $this->taxConfig->displaySalesSubtotalInclTax($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesSubtotalExclTax($store = null)
    {
        return $this->taxConfig->displaySalesSubtotalExclTax($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesSubtotalBoth($store = null)
    {
        return $this->taxConfig->displaySalesSubtotalBoth($store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function displaySalesTaxWithGrandTotal($store = null)
    {
        return $this->taxConfig->displaySalesTaxWithGrandTotal($store);
    }

    /**
     * We need to add Mirakl shipping taxes on order tax details
     * (only if Magento order has not been sent to Mirakl yet)
     *
     * @param   SalesOrder  $order
     * @return  array
     */
    public function getCalculatedTaxes(SalesOrder $order)
    {
        $fullTaxInfo     = [];
        $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($order->getId());
        $appliedTaxes    = $orderTaxDetails->getAppliedTaxes();

        /** @var \Magento\Tax\Model\Sales\Order\Tax $appliedTax */
        foreach ($appliedTaxes as $appliedTax) {
            $taxCode = $appliedTax->getCode();
            $fullTaxInfo[$taxCode]['tax_amount']      = $appliedTax->getAmount();
            $fullTaxInfo[$taxCode]['base_tax_amount'] = $appliedTax->getBaseAmount();
            $fullTaxInfo[$taxCode]['title']           = $appliedTax->getTitle();
            $fullTaxInfo[$taxCode]['percent']         = $appliedTax->getPercent();
        }

        if (!$order->getData('mirakl_sent') || $this->isAdmin) {
            foreach ($order->getAllItems() as $item) {
                if ($miraklShippingTaxApplied = unserialize($item->getMiraklShippingTaxApplied())) {
                    foreach ($miraklShippingTaxApplied as $miraklAppliedTax) {
                        if (isset($fullTaxInfo[$miraklAppliedTax['id']])) {
                            $fullTaxInfo[$miraklAppliedTax['id']]['tax_amount'] += $miraklAppliedTax['amount'];
                            $fullTaxInfo[$miraklAppliedTax['id']]['base_tax_amount'] += $miraklAppliedTax['base_amount'];
                        } else {
                            $miraklAppliedTax['title'] = $miraklAppliedTax['id'];
                            $miraklAppliedTax['tax_amount'] = $miraklAppliedTax['amount'];
                            $miraklAppliedTax['base_tax_amount'] = $miraklAppliedTax['base_amount'];
                            $fullTaxInfo[$miraklAppliedTax['id']] = $miraklAppliedTax;
                        }
                    }
                }
            }
        }

        if ($order->getData('mirakl_sent') && !$this->isAdmin) {
            foreach ($fullTaxInfo as $taxCode => $taxInfo) {
                $taxItems = $this->getMiraklOrderTaxItemsByTaxCode($order->getId(), $taxCode);
                foreach ($taxItems as $taxItem) {
                    $fullTaxInfo[$taxCode]['tax_amount']      -= ($taxItem['row_total'] * $taxItem['tax_percent'] / 100);
                    $fullTaxInfo[$taxCode]['base_tax_amount'] -= ($taxItem['base_row_total'] * $taxItem['tax_percent'] / 100);
                }
            }
        }

        if (!$order->getData('mirakl_sent') || $this->isAdmin) {
            $fullTaxInfo = array_merge($fullTaxInfo, $this->getMiraklCalculatedTaxes($order));
        }

        return $fullTaxInfo;
    }

    /**
     * Returns Mirakl tax details applied on specified order
     *
     * @param   SalesOrder  $order
     * @return  array
     */
    public function getMiraklCalculatedTaxes(SalesOrder $order)
    {
        $miraklTaxes = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($order->getAllItems() as $item) {
            if ($customTaxApplied = unserialize($item->getMiraklCustomTaxApplied())) {
                $customTaxes = array_merge($customTaxApplied['taxes'], $customTaxApplied['shipping_taxes']);
                foreach ($customTaxes as $tax) {
                    $code = 'Marketplace ' . $tax['type'] . '-' . $tax['name'];
                    if (!isset($miraklTaxes[$code])) {
                        $miraklTaxes[$code] = [
                            'tax_amount'      => 0,
                            'base_tax_amount' => 0,
                            'title'           => sprintf('%s (%s)', $tax['name'], $tax['type']),
                            'percent'         => null,
                        ];
                    }
                    $miraklTaxes[$code]['tax_amount'] += $tax['amount'];
                    $miraklTaxes[$code]['base_tax_amount'] += $tax['base_amount'];
                }
            }
        }

        return $miraklTaxes;
    }

    /**
     * @param   SalesOrder  $order
     * @param   MiraklOrder $miraklOrder
     * @return  array
     */
    public function getMiraklOrderCalculatedTaxes($order, MiraklOrder $miraklOrder)
    {
        $fullTaxInfo = [];

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $offerId = $orderLine->getOffer()->getId();

            foreach ($order->getAllItems() as $item) {
                if ($item->getMiraklOfferId() != $offerId) {
                    continue;
                }

                if ($miraklShippingTaxApplied = unserialize($item->getMiraklShippingTaxApplied())) {
                    foreach ($miraklShippingTaxApplied as $miraklAppliedTax) {
                        if (isset($fullTaxInfo[$miraklAppliedTax['id']])) {
                            $fullTaxInfo[$miraklAppliedTax['id']]['tax_amount'] += $miraklAppliedTax['amount'];
                            $fullTaxInfo[$miraklAppliedTax['id']]['base_tax_amount'] += $miraklAppliedTax['base_amount'];
                        } else {
                            $miraklAppliedTax['title'] = $miraklAppliedTax['id'];
                            $miraklAppliedTax['tax_amount'] = $miraklAppliedTax['amount'];
                            $miraklAppliedTax['base_tax_amount'] = $miraklAppliedTax['base_amount'];
                            $fullTaxInfo[$miraklAppliedTax['id']] = $miraklAppliedTax;
                        }
                    }
                }
            }

            $taxItems = $this->getMiraklOrderTaxItemsByOfferId($order->getId(), $orderLine->getOffer()->getId());
            foreach ($taxItems as $taxItem) {
                $taxCode = $taxItem['tax_code'];
                if (!isset($fullTaxInfo[$taxCode])) {
                    $fullTaxInfo[$taxCode] = [
                        'id'              => $taxCode,
                        'title'           => $taxItem['tax_title'],
                        'percent'         => (float) $taxItem['tax_percent'],
                        'tax_amount'      => 0,
                        'base_tax_amount' => 0,
                    ];
                }
                $fullTaxInfo[$taxCode]['tax_amount']      += ($taxItem['row_total'] * $taxItem['tax_percent'] / 100);
                $fullTaxInfo[$taxCode]['base_tax_amount'] += ($taxItem['base_row_total'] * $taxItem['tax_percent'] / 100);
            }
        }

        return $fullTaxInfo;
    }

    /**
     * Returns Magento tax items applied on a specific Mirakl offer
     *
     * @param   int $orderId
     * @param   int $offerId
     * @return  array
     */
    protected function getMiraklOrderTaxItemsByOfferId($orderId, $offerId)
    {
        $resource = $this->taxOrderResourceFactory->create();
        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from(
                ['order_item_taxes' => $resource->getTable('sales_order_tax_item')],
                ['item_id', 'tax_percent']
            )
            ->join(
                ['order_taxes' => $resource->getTable('sales_order_tax')],
                'order_item_taxes.tax_id = order_taxes.tax_id',
                ['tax_code' => 'code', 'tax_title' => 'title']
            )
            ->join(
                ['order_items' => $resource->getTable('sales_order_item')],
                'order_item_taxes.item_id = order_items.item_id',
                ['row_total', 'base_row_total', 'mirakl_shipping_fee', 'mirakl_base_shipping_fee']
            )
            ->where('order_taxes.order_id = ?', $orderId)
            ->where('order_items.mirakl_offer_id = ?', $offerId);

        return $connection->fetchAll($select);
    }

    /**
     * Returns Magento tax items applied on Mirakl order items
     *
     * @param   int     $orderId
     * @param   string  $taxCode
     * @return  array
     */
    protected function getMiraklOrderTaxItemsByTaxCode($orderId, $taxCode)
    {
        $resource = $this->taxOrderResourceFactory->create();
        $connection = $resource->getConnection();

        $select = $connection->select()
            ->from(
                ['order_item_taxes' => $resource->getTable('sales_order_tax_item')],
                ['item_id', 'tax_percent']
            )
            ->join(
                ['order_taxes' => $resource->getTable('sales_order_tax')],
                'order_item_taxes.tax_id = order_taxes.tax_id',
                ''
            )
            ->join(
                ['order_items' => $resource->getTable('sales_order_item')],
                'order_item_taxes.item_id = order_items.item_id',
                ['row_total', 'base_row_total', 'mirakl_shipping_fee', 'mirakl_base_shipping_fee']
            )
            ->where('order_taxes.order_id = ?', $orderId)
            ->where('order_taxes.code = ?', $taxCode)
            ->where('order_items.mirakl_offer_id > 0');

        return $connection->fetchAll($select);
    }

    /**
     * Subtract tax amount from specified price
     *
     * @param   float                   $price
     * @param   int                     $taxClassId
     * @param   AddressInterface|null   $shippingAddress
     * @return  float
     */
    public function getPriceExclTax($price, $taxClassId, $shippingAddress = null)
    {
        $rate = $this->getTaxRate($taxClassId, $shippingAddress);

        if (!$rate) {
            return $price;
        }

        return $price / (1 + ($rate / 100));
    }

    /**
     * Add tax amount to specified price
     *
     * @param   float                   $price
     * @param   int                     $taxClassId
     * @param   AddressInterface|null   $shippingAddress
     * @return  float
     */
    public function getPriceInclTax($price, $taxClassId, $shippingAddress = null)
    {
        $rate = $this->getTaxRate($taxClassId, $shippingAddress);

        if (!$rate) {
            return $price;
        }

        return $price + ($price * $rate / 100);
    }

    /**
     * Subtract tax amount from specified price
     *
     * @param   float                   $price
     * @param   AddressInterface|null   $shippingAddress
     * @return  float
     */
    public function getShippingPriceExclTax($price, $shippingAddress = null)
    {
        $rate = $this->getTaxRate($this->getShippingTaxClass(), $shippingAddress);

        if (!$rate) {
            return $price;
        }

        return $price / (1 + ($rate / 100));
    }

    /**
     * Add tax amount to specified price
     *
     * @param   float                   $price
     * @param   AddressInterface|null   $shippingAddress
     * @return  float
     */
    public function getShippingPriceInclTax($price, $shippingAddress = null)
    {
        $rate = $this->getTaxRate($this->getShippingTaxClass(), $shippingAddress);

        if (!$rate) {
            return $price;
        }

        return $price + ($price * $rate / 100);
    }

    /**
     * @return  int
     */
    public function getShippingTaxClass()
    {
        return $this->scopeConfig->getValue(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS);
    }

    /**
     * @param   int                     $taxClassId
     * @param   AddressInterface|null   $shippingAddress
     * @return  float
     */
    protected function getTaxRate($taxClassId, $shippingAddress = null)
    {
        if (!isset($this->taxRates[$taxClassId])) {
            /** @var \Magento\Tax\Model\Calculation $calculator */
            $rateRequest = $this->taxCalculation->getRateRequest($shippingAddress);
            $this->taxRates[$taxClassId] = $this->taxCalculation->getRate($rateRequest->setProductClassId($taxClassId));
        }

        return $this->taxRates[$taxClassId];
    }
}
