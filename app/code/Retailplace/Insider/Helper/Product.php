<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Data;
use Magento\Framework\UrlInterface;
use Mirakl\Connector\Model\Offer as OfferModel;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Product class
 */
class Product extends AbstractHelper
{
    /** @var Data */
    private $catalogData;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var ImageHelper */
    private $imageHelper;

    /**
     * Product constructor
     *
     * @param Data $catalogData
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $urlBuilder
     * @param ImageHelper $imageHelper
     * @param Context $context
     */
    public function __construct(
        Data $catalogData,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        ImageHelper $imageHelper,
        Context $context
    ) {
        $this->storeManager = $storeManager;
        $this->catalogData = $catalogData;
        $this->urlBuilder = $urlBuilder;
        $this->imageHelper = $imageHelper;

        parent::__construct($context);
    }

    /**
     * Get product data
     *
     * @param \Magento\Catalog\Model\Product|ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductData(ProductInterface $product): array
    {
        /** @var OfferModel $offer */
        $offer = $product->getData('main_offer');
        $unitPrice = 0;
        $unitSalePrice = 0;
        $shopId = null;
        if ($offer) {
            $unitPrice = $offer->getOriginPrice();
            $unitSalePrice = $offer->getPrice();
            $shopId = $offer->getShopId();
        }

        return [
            'id'                => $product->getData('sku'),
            'name'              => $product->getData('name'),
            'taxonomy'          => $this->getTaxonomy() ?? "",
            "currency"          => $this->getCurrency(),
            "unit_price"        => $unitPrice,
            "unit_sale_price"   => $unitSalePrice,
            "url"               => $this->getProductUrl($product, $shopId),
            "product_image_url" => $this->imageHelper
                ->init($product, 'category_page_grid')
                ->getUrl()
        ];
    }

    /**
     * Get Taxonomy
     *
     * @return array
     */
    public function getTaxonomy(): array
    {
        $categoryTree = [];
        if ($this->catalogData->getBreadcrumbPath()) {
            $categories = $this->catalogData->getBreadcrumbPath();
            foreach ($categories as $category) {
                $categoryTree[] = $category['label'];
            }
        }

        return $categoryTree;
    }

    /**
     * Get default store currency code
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getCurrency(): ?string
    {
        return $this->storeManager->getStore()->getDefaultCurrencyCode();
    }

    /**
     * Get product link
     *
     * @param ProductInterface $product
     * @param int|null $shopId
     * @return string
     */
    public function getProductUrl(ProductInterface $product, ?int $shopId = null): string
    {
        if ($shopId) {
            $productUrl = $this->urlBuilder->getUrl('seller') . $shopId . '/' . $product->getUrlKey() . '.html';
        } else {
            $productUrl = $product->getUrlModel()->getUrl($product);
        }

        return $productUrl;
    }
}
