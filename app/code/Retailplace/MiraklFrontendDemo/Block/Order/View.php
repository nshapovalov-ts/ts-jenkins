<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Block\Order;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\Config;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Mirakl\Connector\Helper\Order as OrderHelper;
use Mirakl\MMP\FrontOperator\Domain\Order\OrderLine;
use Magento\Sales\Model\Order\Item;

class View extends \Mirakl\FrontendDemo\Block\Order\View
{
    /**
     * @var string
     */
    protected $_template = 'Mirakl_FrontendDemo::order/view.phtml';
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var ItemCollection
     */
    protected $orderItemCollection;
    /**
     * @var array
     */
    protected $_product;
    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var Collection
     */
    protected $productCollection;
    /**
     * @var Config
     */
    protected $attributeConfig;

    /**
     * @var Item[]
     */
    private $allOrderItems;

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param HttpContext $httpContext
     * @param OrderHelper $orderHelper
     * @param OrderFactory $orderFactory
     * @param CollectionFactory $productCollectionFactory
     * @param Config $attributeConfig
     * @param array $data
     * @noinspection PhpUndefinedClassInspection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        HttpContext $httpContext,
        OrderHelper $orderHelper,
        OrderFactory $orderFactory,
        CollectionFactory $productCollectionFactory,
        Config $attributeConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $httpContext, $orderHelper, $data);
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->attributeConfig = $attributeConfig;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param $sku
     * @return ProductInterface|Product|null
     */
    public function getProduct($sku)
    {
        if (!isset($this->_product[$sku])) {
            $orderItemCollection = $this->getOrderItemCollection();
            $orderItem = $this->getItemByColumnValue($orderItemCollection, 'sku', $sku);
            if ($orderItem && $parentItemId = $orderItem->getData('parent_item_id')) {
                $parentItem = $orderItemCollection->getItemById($parentItemId);
                if ($productId = $parentItem->getData('product_id')) {
                    $this->_product[$sku] = $this->productCollection->getItemById($productId);
                    return $this->_product[$sku];
                }
            }
            $this->_product[$sku] = $this->productCollection->getItemByColumnValue('sku', $sku);
        }
        return $this->_product[$sku];
    }

    /**
     * It is used to find children of configurable items
     * @param $orderItemCollection
     * @param $column
     * @param $value
     * @return false
     */
    public function getItemByColumnValue($orderItemCollection, $column, $value)
    {
        foreach ($orderItemCollection as $item) {
            if ($item->getData('parent_item_id') && $item->getData($column) == $value) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @return ItemCollection
     */
    public function getOrderItemCollection(): ItemCollection
    {
        if ($this->orderItemCollection == null) {
            $orderId = $this->getRequest()->getParam('order_id');
            $_order = $this->orderFactory->create()->load($orderId);
            $this->orderItemCollection = $_order->getItemsCollection();
            $productIds = $this->orderItemCollection->getColumnValues('product_id');
            if ($productIds) {
                $this->productCollection = $this->_productCollectionFactory->create()->setStoreId(
                    $_order->getStoreId()
                )->addIdFilter(
                    $productIds
                )->addAttributeToSelect(
                    $this->attributeConfig->getAttributeNames('quote_item')
                );

                $this->productCollection->setFlag('has_stock_status_filter', true);
                $this->productCollection->addOptionsToResult()->addStoreFilter()->addUrlRewrite();
            }
        }

        return $this->orderItemCollection;
    }

    /**
     * Get Order Line Item
     *
     * @param   OrderLine   $orderLine
     * @return  Item|null
     */
    private function getOrderLineItem(OrderLine $orderLine): ?Item
    {
        if ($orderLine->getOffer()->getId()) {
            foreach ($this->getOrder()->getAllItems() as $item) {
                if ($item->getMiraklOfferId() == $orderLine->getOffer()->getId()) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Get Order Line Tax Amount
     *
     * @param   OrderLine   $orderLine
     * @return  float
     */
    public function getOrderLineTaxAmount(OrderLine $orderLine): float
    {
        if ($item = $this->getOrderLineItem($orderLine)) {
            return (float) $item->getTaxAmount();
        }

        return (float) 0;
    }

    /**
     * Get Order Shipping Tax
     *
     * @return float
     */
    public function getOrderShippingTax(): float
    {
        return $this->orderHelper->getMiraklShippingTaxAmount($this->getOrder(), $this->getMiraklOrder());
    }

    /**
     * Get Offer Shop Id For Order Line
     *
     * @param   OrderLine   $orderLine
     * @return  int|null
     */
    public function getOfferShopIdForOrderLine(OrderLine $orderLine): ?int
    {
        if (!$orderLine->getOffer()->getId()) {
            return null;
        }

        if ($this->allOrderItems === null) {
            $this->allOrderItems = $this->getOrder()->getAllItems();

            foreach ($this->allOrderItems as $item) {
                if ($item->getProductType() === "simple") {
                    $parentItem = $item->getParentItem();
                    if (!empty($parentItem)) {
                        $item->setMiraklShopId($parentItem->getMiraklShopId());
                    }
                }
            }
        }

        foreach ($this->allOrderItems as $item) {
            if ($item->getMiraklOfferId() == $orderLine->getOffer()->getId()) {
                return (int) $item->getMiraklShopId();
            }
        }

        return null;
    }
}
