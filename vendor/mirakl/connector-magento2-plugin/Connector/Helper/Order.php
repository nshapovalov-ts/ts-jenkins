<?php
namespace Mirakl\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\ResourceModel\OrderFactory as OrderResourceFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Mirakl\Api\Helper\Order as OrderApiHelper;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Order\Converter as OrderConverter;
use Mirakl\Core\Helper\Config as CoreConfig;
use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\Front\Domain\Order\Create\CreatedOrders;
use Mirakl\MMP\FrontOperator\Domain\Order\OrderLine;
use Mirakl\MMP\FrontOperator\Domain\Order as MiraklOrder;

class Order extends AbstractHelper
{
    /**
     * @var CoreConfig
     */
    protected $coreConfig;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var OrderApiHelper
     */
    protected $orderApiHelper;

    /**
     * @var OrderConverter
     */
    protected $orderConverter;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var OrderResourceFactory
     */
    protected $orderResourceFactory;

    /**
     * @param   Context                 $context
     * @param   CoreConfig              $coreConfig
     * @param   ConnectorConfig         $connectorConfig
     * @param   OrderApiHelper          $orderApiHelper
     * @param   OrderConverter          $orderConverter
     * @param   OrderResourceFactory    $orderResourceFactory
     * @param   State                   $appState
     */
    public function __construct(
        Context $context,
        CoreConfig $coreConfig,
        ConnectorConfig $connectorConfig,
        OrderApiHelper $orderApiHelper,
        OrderConverter $orderConverter,
        OrderResourceFactory $orderResourceFactory,
        State $appState
    ) {
        parent::__construct($context);
        $this->coreConfig           = $coreConfig;
        $this->orderApiHelper       = $orderApiHelper;
        $this->orderConverter       = $orderConverter;
        $this->orderResourceFactory = $orderResourceFactory;
        $this->appState             = $appState;
        $this->connectorConfig      = $connectorConfig;
    }

    /**
     * Adds Mirakl orders information to specified Magento orders
     *
     * @param   OrderCollection  $collection
     * @return  $this
     */
    public function addMiraklOrdersToCollection(OrderCollection $collection)
    {
        $commercialIds = [];
        foreach ($collection as $order) {
            /** @var OrderModel $order */
            if ($order->getMiraklSent()) {
                $commercialIds[] = $order->getIncrementId();
            }
        }

        if (empty($commercialIds)) {
            return $this;
        }

        $miraklOrders = $this->orderApiHelper->getOrdersByCommercialId($commercialIds);
        foreach ($collection as $order) {
            $addOrders = [];
            foreach ($miraklOrders as $miraklOrder) {
                /** @var MiraklOrder $miraklOrder */
                if ($miraklOrder->getCommercialId() == $order->getIncrementId()) {
                    $addOrders[] = $miraklOrder;
                }
            }
            $order->setMiraklOrders($addOrders);
        }

        return $this;
    }

    /**
     * Creates Mirakl order and set Magento order as sent if creation succeeded
     *
     * @param   OrderModel  $order
     * @param   bool        $markAsSent
     * @return  CreatedOrders
     * @throws  \Exception
     */
    public function createMiraklOrder(OrderModel $order, $markAsSent = true)
    {
        $createdOffers = $this->orderApiHelper->createOrder($this->orderConverter->convert($order));

        if ($markAsSent && $createdOffers->getOrders()->count()) {
            $order->setMiraklSent(1);
            $this->orderResourceFactory->create()->saveAttribute($order, 'mirakl_sent');
        }

        return $createdOffers;
    }

    /**
     * Returns shipping price in base currency of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklBaseShippingPriceInclTax(OrderModel $order, MiraklOrder $miraklOrder)
    {
        $baseShippingPrice = $this->getMiraklTotal($order, $miraklOrder, ['mirakl_base_shipping_fee']);

        if ($order->getMiraklIsShippingInclTax()) {
            return $baseShippingPrice;
        }

        $baseTaxAmount = $this->getMiraklTotal($order, $miraklOrder, [
            'mirakl_base_shipping_tax_amount',
            'mirakl_base_custom_shipping_tax_amount'
        ]);

        return $baseShippingPrice + $baseTaxAmount;
    }

    /**
     * Returns shipping price of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklShippingPriceInclTax(OrderModel $order, MiraklOrder $miraklOrder)
    {
        $shippingPrice = $this->getMiraklTotal($order, $miraklOrder, ['mirakl_shipping_fee']);

        if ($order->getMiraklIsShippingInclTax()) {
            return $shippingPrice;
        }

        $taxAmount = $this->getMiraklTotal($order, $miraklOrder, [
            'mirakl_shipping_tax_amount',
            'mirakl_custom_shipping_tax_amount'
        ]);

        return $shippingPrice + $taxAmount;
    }

    /**
     * Returns shipping price excluding tax of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklShippingPriceExclTax(OrderModel $order, MiraklOrder $miraklOrder)
    {
        $shippingPrice = $this->getMiraklTotal($order, $miraklOrder, ['mirakl_shipping_fee']);

        if (!$order->getMiraklIsShippingInclTax()) {
            return $shippingPrice;
        }

        $taxAmount = $this->getMiraklTotal($order, $miraklOrder, [
            'mirakl_shipping_tax_amount',
            'mirakl_custom_shipping_tax_amount'
        ]);

        return $shippingPrice - $taxAmount;
    }

    /**
     * Returns subtotal price of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklSubtotalPrice(OrderModel $order, MiraklOrder $miraklOrder)
    {
        return $this->getMiraklTotal($order, $miraklOrder, ['row_total_incl_tax']);
    }

    /**
     * Returns subtotal price excluding tax of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklSubtotalPriceExclTax(OrderModel $order, MiraklOrder $miraklOrder)
    {
        return $this->getMiraklTotal($order, $miraklOrder, ['row_total']);
    }

    /**
     * Returns base total price of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklBaseTotalPrice(OrderModel $order, MiraklOrder $miraklOrder)
    {
        $baseTotalPrice = $this->getMiraklTotal($order, $miraklOrder, [
            'base_row_total_incl_tax',
            'mirakl_base_shipping_fee'
        ]);

        if ($order->getMiraklIsShippingInclTax()) {
            return $baseTotalPrice;
        }

        $baseShippingTax = $this->getMiraklTotal($order, $miraklOrder, [
            'mirakl_base_shipping_tax_amount',
            'mirakl_base_custom_shipping_tax_amount'
        ]);

        return $baseTotalPrice + $baseShippingTax;
    }

    /**
     * Returns total price of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklTotalPrice(OrderModel $order, MiraklOrder $miraklOrder)
    {
        $totalPrice = $this->getMiraklTotal($order, $miraklOrder, ['row_total_incl_tax', 'mirakl_shipping_fee']);

        if ($order->getMiraklIsShippingInclTax()) {
            return $totalPrice;
        }

        $shippingTax = $this->getMiraklTotal($order, $miraklOrder, [
            'mirakl_shipping_tax_amount',
            'mirakl_custom_shipping_tax_amount'
        ]);

        return $totalPrice + $shippingTax;
    }

    /**
     * Returns total tax amount of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklTotalTax(OrderModel $order, MiraklOrder $miraklOrder)
    {
        return $this->getMiraklTotal($order, $miraklOrder, [
            'tax_amount',
            'mirakl_shipping_tax_amount',
            'mirakl_custom_shipping_tax_amount',
        ]);
    }

    /**
     * Returns total price excluding tax of specified Magento order including only Mirakl items
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @return  float
     */
    public function getMiraklTotalPriceExclTax(OrderModel $order, MiraklOrder $miraklOrder)
    {
        return $this->getMiraklSubtotalPriceExclTax($order, $miraklOrder)
             + $this->getMiraklShippingPriceExclTax($order, $miraklOrder);
    }

    /**
     * Returns Mirakl order total of specified order item fields
     *
     * @param   OrderModel  $order
     * @param   MiraklOrder $miraklOrder
     * @param   array       $orderItemFields
     * @return  float
     */
    private function getMiraklTotal(OrderModel $order, MiraklOrder $miraklOrder, array $orderItemFields)
    {
        $total = 0;

        foreach ($order->getItemsCollection() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if ($item->getMiraklOfferId()) {
                /** @var OrderLine $orderLine */
                foreach ($miraklOrder->getOrderLines() as $orderLine) {
                    if ($orderLine->getOffer() && $orderLine->getOffer()->getId() == $item->getMiraklOfferId()) {
                        foreach ($orderItemFields as $field) {
                            $total += $item->getData($field);
                        }
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Returns Mirakl order associated with specified order commercial id and remote id
     *
     * @param   string  $commercialId
     * @param   string  $remoteId
     * @return  MiraklOrder
     */
    public function getMiraklOrderById($commercialId, $remoteId)
    {
        $locale = $this->coreConfig->getLocale();
        $miraklOrders = $this->orderApiHelper->getOrdersByCommercialId($commercialId, false, $locale);
        foreach ($miraklOrders as $miraklOrder) {
            /** @var MiraklOrder $miraklOrder */
            if ($miraklOrder->getId() == $remoteId) {
                return $miraklOrder;
            }
        }

        return null;
    }

    /**
     * @param   OrderModel  $order
     * @return  float
     */
    public function getOperatorBaseGrandTotalInclTax(OrderModel $order)
    {
        $baseGrandTotalInclTax = $order->getBaseGrandTotal();

        foreach ($order->getAllItems() as $item) {
            if (!$item->getMiraklShopId() || $item->getParentItem()) {
                continue;
            }
            $baseGrandTotalInclTax -= $item->getBaseRowTotalInclTax();
        }

        return $baseGrandTotalInclTax - $this->getOperatorBaseShippingInclTax($order);
    }

    /**
     * @param   OrderModel  $order
     * @return  float
     */
    public function getOperatorBaseShippingExclTax(OrderModel $order)
    {
        $baseShippingExclTax = $order->getBaseShippingAmount() - $order->getMiraklBaseShippingFee();

        if ($this->isShippingPricesIncludeTax($order)) {
            // Mirakl shipping price is INCLUDING tax
            $baseShippingExclTax = $baseShippingExclTax
                + $order->getMiraklBaseShippingTaxAmount()
                + $order->getMiraklBaseCustomShippingTaxAmount();
        }

        return $baseShippingExclTax;
    }

    /**
     * @param   OrderModel  $order
     * @return  float
     */
    public function getOperatorBaseShippingInclTax(OrderModel $order)
    {
        $baseShippingInclTax = $order->getBaseShippingInclTax() - $order->getMiraklBaseShippingFee();

        if (!$this->isShippingPricesIncludeTax($order)) {
            // Mirakl shipping price is EXCLUDING tax
            $baseShippingInclTax = $baseShippingInclTax
                - $order->getMiraklBaseShippingTaxAmount()
                - $order->getMiraklBaseCustomShippingTaxAmount();
        }

        return $baseShippingInclTax;
    }

    /**
     * @param   OrderModel  $order
     * @return  float
     */
    public function getOperatorGrandTotalInclTax(OrderModel $order)
    {
        $grandTotalInclTax = $order->getGrandTotal();

        foreach ($order->getAllItems() as $item) {
            if (!$item->getMiraklShopId() || $item->getParentItem()) {
                continue;
            }
            $grandTotalInclTax -= $item->getRowTotalInclTax();
        }

        $grandTotalInclTax -= $order->getMiraklShippingFee();

        if (!$this->isShippingPricesIncludeTax($order)) {
            // Mirakl shipping price is EXCLUDING tax
            $grandTotalInclTax = $grandTotalInclTax
                - $order->getMiraklShippingTaxAmount()
                - $order->getMiraklCustomShippingTaxAmount();
        }

        return $grandTotalInclTax;
    }

    /**
     * @param   OrderModel  $order
     * @return  float
     */
    public function getOperatorShippingExclTax(OrderModel $order)
    {
        $shippingExclTax = $order->getShippingAmount() - $order->getMiraklShippingFee();

        if ($this->isShippingPricesIncludeTax($order)) {
            // Mirakl shipping price is INCLUDING tax
            $shippingExclTax = $shippingExclTax
                + $order->getMiraklShippingTaxAmount()
                + $order->getMiraklCustomShippingTaxAmount();
        }

        return $shippingExclTax;
    }

    /**
     * @param   OrderModel  $order
     * @return  float
     */
    public function getOperatorShippingInclTax(OrderModel $order)
    {
        $shippingInclTax = $order->getShippingInclTax() - $order->getMiraklShippingFee();

        if (!$this->isShippingPricesIncludeTax($order)) {
            // Mirakl shipping price is EXCLUDING tax
            $shippingInclTax = $shippingInclTax
                - $order->getMiraklShippingTaxAmount()
                - $order->getMiraklCustomShippingTaxAmount();
        }

        return $shippingInclTax;
    }

    /**
     * Returns shipping description of specified order including Mirakl order items
     *
     * @param   OrderModel  $order
     * @return  string
     */
    public function getShippingDescription(OrderModel $order)
    {
        $labels = [];

        if (!$order->getId()) {
            return $order->getData(OrderInterface::SHIPPING_DESCRIPTION);
        }

        if (!$order->getMiraklSent() || $this->isAdmin()) {
            foreach ($order->getAllItems() as $item) {
                if ($item->getMiraklShopId()) {
                    $labels[] = $item->getMiraklShippingTypeLabel();
                }
            }
        }

        if (!$this->isFullMiraklOrder($order)) {
            array_unshift($labels, $order->getData(OrderInterface::SHIPPING_DESCRIPTION));
        }

        return implode(', ', array_unique(array_filter($labels)));
    }

    /**
     * @return  bool
     */
    private function isAdmin()
    {
        return $this->appState->getAreaCode() == 'adminhtml';
    }

    /**
     * Returns true if the given Magento order contains ONLY Mirakl offers
     *
     * @param   OrderModel  $order
     * @return  bool
     */
    public function isFullMiraklOrder(OrderModel $order)
    {
        foreach ($order->getAllItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if (!$item->isDeleted() && !$item->getParentItemId() && !$item->getMiraklShopId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if given order line has an incident open
     *
     * @param   OrderLine   $orderLine
     * @return  bool
     */
    public function isOrderLineIncident(OrderLine $orderLine)
    {
        return $orderLine->getStatus() && $orderLine->getStatus()->getState() == ReasonType::INCIDENT_OPEN;
    }

    /**
     * Returns true if given order line has been refused by the seller
     *
     * @param   OrderLine   $orderLine
     * @return  bool
     */
    public function isOrderLineRefused(OrderLine $orderLine)
    {
        return $orderLine->getStatus() && $orderLine->getStatus()->getState() == ReasonType::REFUSED;
    }

    /**
     * Returns true if the given Magento order contains SOME Mirakl offers
     *
     * @param   OrderModel  $order
     * @return  bool
     */
    public function isMiraklOrder(OrderModel $order)
    {
        foreach ($order->getAllItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if (!$item->isDeleted() && !$item->getParentItemId() && $item->getMiraklShopId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param   OrderModel  $order
     * @return  bool
     */
    public function isShippingPricesIncludeTax(OrderModel $order)
    {
        if ($order->hasData('mirakl_is_shipping_incl_tax')) {
            return (bool) $order->getData('mirakl_is_shipping_incl_tax');
        }

        return $this->connectorConfig->getShippingPricesIncludeTax($order->getStoreId());
    }
}
