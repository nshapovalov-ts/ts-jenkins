<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Magento\Quote\Api\Data\CartItemInterface;
use Retailplace\Insider\Api\InsiderObjectProviderInterface;
use Magento\Checkout\Model\Session;
use Retailplace\Insider\Helper\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Vdcstore\CategoryTree\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * BasketObjectProvider class
 */
class BasketObjectProvider implements InsiderObjectProviderInterface
{
    /** @var Product */
    private $productHelper;

    /** @var Session */
    private $checkoutSession;

    /** @var array */
    private $productsData;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var CategoryCollectionFactory */
    private $categoryCollectionFactory;

    /** @var CategoryInterface[] */
    private $categories;

    /**
     * BasketObjectProvider constructor
     *
     * @param Product $productHelper
     * @param Session $checkoutSession
     * @param ImageHelper $imageHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        Product $productHelper,
        Session $checkoutSession,
        ImageHelper $imageHelper,
        ScopeConfigInterface $scopeConfig,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->productHelper = $productHelper;
        $this->checkoutSession = $checkoutSession;
        $this->imageHelper = $imageHelper;
        $this->scopeConfig = $scopeConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Get cart config data
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $config = [];
        $this->productsData = [];
        $quote = $this->getQuote();
        if ($quote && $quote->getItems()) {
            $config = [
                'basket' => [
                    'currency'      => $this->productHelper->getCurrency(),
                    'total'         => $quote->getData('base_grand_total'),
                    'shipping_cost' => $quote->getData('shipping_amount'),
                    'line_items'    => $this->getlineItems()
                ]
            ];
        }

        return $config;
    }

    /**
     * Get quote
     *
     * @return false|CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getQuote()
    {
        return $this->checkoutSession->getQuote() ?? false;
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
        if ($allItems = $this->getQuote()->getItems()) {
            $this->fillCategoriesList($allItems);

            foreach ($allItems as $item) {
                $this->productsData[] = $this->getProductData($item);
            }
        }

        return $this->productsData;
    }

    /**
     * Get categories for products
     *
     * @param CartItemInterface[]|null $items
     * @return void
     * @throws LocalizedException
     */
    private function fillCategoriesList(?array $items)
    {
        $categoryIds = [];
        /** @var Item $item */
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
     * Get product data
     *
     * @param CartItemInterface|Item $item
     * @return array[]
     * @throws NoSuchEntityException
     */
    private function getProductData(CartItemInterface $item): array
    {
        $product = $item->getProduct();
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
                "unit_price"        => (float) $item->getBasePriceInclTax(),
                "unit_sale_price"   => $item->getBasePriceInclTax() - ($item->getBaseDiscountAmount() / ($item->getData('qty'))),
                "url"               => $this->productHelper->getProductUrl($product, (int) $item->getMiraklShopId()),
                "product_image_url" => $this->imageHelper
                    ->init($product, 'category_page_grid')
                    ->getUrl(),
                'quantity'          => (int) ($item->getData('qty')),
                'subtotal'          => (float) $item->getBaseRowTotalInclTax()
            ]
        ];
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
