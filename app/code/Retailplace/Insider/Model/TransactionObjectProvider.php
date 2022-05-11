<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Magento\Sales\Model\Order\Item;
use Retailplace\Insider\Api\InsiderObjectProviderInterface;
use Retailplace\Insider\Helper\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\Data\CategoryInterface;
use Vdcstore\CategoryTree\Helper\Data;

/**
 * TransactionObjectProvider class
 */
class TransactionObjectProvider implements InsiderObjectProviderInterface
{
    /** @var RequestInterface */
    private $request;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var Product */
    private $productHelper;

    /** @var array */
    private $productsData;

    /** @var Session */
    private $checkoutSession;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var CategoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var CategoryInterface[] */
    private $categories;

    /** @var null|Order */
    private $order = null;

    /**
     * TransactionObjectProvider constructor
     *
     * @param Session $checkoutSession
     * @param RequestInterface $request
     * @param Product $productHelper
     * @param ImageHelper $imageHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Session $checkoutSession,
        RequestInterface $request,
        Product $productHelper,
        ImageHelper $imageHelper,
        ScopeConfigInterface $scopeConfig,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->productHelper = $productHelper;
        $this->imageHelper = $imageHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Get config
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $config = [];
        $this->productsData = [];
        if ($this->request->getFullActionName() === 'checkout_onepage_success' && $this->getOrderId()) {
            $order = $this->getOrder();
            $orderData = $order->getData();
            $config = [
                'transaction' => [
                    'order_id'      => $orderData['entity_id'] ?? '',
                    'currency'      => $orderData['base_currency_code'] ?? '',
                    'total'         => floatval($orderData['base_grand_total']) ?? '',
                    'shipping_cost' => floatval($orderData['base_shipping_amount']) ?? '',
                    'line_items'    => $this->getlineItems()
                ]
            ];
        }

        return $config;
    }

    /**
     * Get line items
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getLineItems(): array
    {
        if ($allItems = $this->getOrder()->getItems()) {
            $this->fillCategoriesList($allItems);
            foreach ($allItems as $item) {
                $this->productsData[] = $this->getProductData($item);
            }
        }

        return $this->productsData;
    }

    /**
     * Get product data
     *
     * @param OrderItemInterface|Item $orderItem
     * @return array[]
     * @throws NoSuchEntityException
     */
    private function getProductData(OrderItemInterface $orderItem): array
    {
        $product = $orderItem->getProduct();
        $rootCategoryId = $this->getRootCategoryId();
        $taxonomy = [];
        foreach ($this->categories as $category) {
            if (in_array($rootCategoryId, $category->getParentIds())) {
                $taxonomy[] = $category->getName();
            }
        }

        return [
            'product' => [
                'id'                => $product->getData('sku'),
                'type'              => $product->getData('type_id'),
                'name'              => $product->getData('name'),
                'taxonomy'          => $taxonomy,
                "currency"          => $this->productHelper->getCurrency(),
                "unit_price"        => (float) $orderItem->getBasePriceInclTax(),
                "unit_sale_price"   => $orderItem->getBasePriceInclTax() - ($orderItem->getBaseDiscountAmount() / ($orderItem->getQtyOrdered())),
                "url"               => $this->productHelper->getProductUrl($product, (int) $orderItem->getMiraklShopId()),
                "product_image_url" => $this->imageHelper
                    ->init($product, 'category_page_grid')
                    ->getUrl(),
                'quantity'          => (int) ($orderItem->getQtyOrdered()),
                'subtotal'          => (float) $orderItem->getBaseRowTotalInclTax()
            ]
        ];
    }

    /**
     * Get order id
     *
     * @return int|false
     */
    private function getOrderId()
    {
        $this->checkoutSession->getLastOrderId();

        return $this->checkoutSession->getLastOrderId() ?? false;
    }

    /**
     * Get order
     *
     * @return null|Order
     */
    private function getOrder(): ?Order
    {
        if (!$this->order && $this->getOrderId()) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }

        return $this->order;
    }

    /**
     * Get categories for products
     *
     * @param OrderItemInterface[]|null $items
     * @return void
     * @throws LocalizedException
     */
    private function fillCategoriesList(?array $items)
    {
        $categoryIds = [];
        foreach ($items as $item) {
            $categoryIds = array_merge($item->getProduct()->getCategoryIds(), $categoryIds);
        }
        $categoryIds = array_unique($categoryIds);
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addFieldToFilter('entity_id', ['in' => $categoryIds]);
        $categoryCollection->addAttributeToSelect(CategoryInterface::KEY_NAME);
        $this->categories = $categoryCollection->getItems();
    }

    /**
     * Get root category id
     *
     * @return int
     */
    private function getRootCategoryId(): int
    {
        return (int) $this->scopeConfig->getValue(Data::MENU_ROOT_CATEGORY);
    }
}
