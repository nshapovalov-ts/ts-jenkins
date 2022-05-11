<?php

/**
 * Retailplace_Insider
 *
 * @copyright   Copyright © 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Insider\Model;

use Retailplace\Insider\Api\InsiderObjectProviderInterface;
use Retailplace\Insider\Helper\Product as ProductHelper;
use Magento\Framework\Registry;
use Magento\Catalog\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * ProductObjectProvider class
 */
class ProductObjectProvider implements InsiderObjectProviderInterface
{
    /** @var Registry */
    private $registry;

    /** @var Data */
    private $catalogData;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var ProductHelper */
    private $helperProduct;

    /**
     * ProductObjectProvider constructor
     *
     * @param Registry $registry
     * @param Data $catalogData
     * @param StoreManagerInterface $storeManager
     * @param ImageHelper $imageHelper
     * @param ProductHelper $helperProduct
     */
    public function __construct(
        Registry $registry,
        Data $catalogData,
        StoreManagerInterface $storeManager,
        ImageHelper $imageHelper,
        ProductHelper $helperProduct
    ) {
        $this->registry = $registry;
        $this->catalogData = $catalogData;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->helperProduct = $helperProduct;
    }

    /**
     * Get config
     *
     * @return array|array[]
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $config = [];
        $product = $this->getCurrentProduct();
        if ($product) {
            $offer = $product->getData('main_offer');
            $shopId = null;
            if ($offer) {
                $shopId = $offer->getShopId();
            }

            $config = [
                'product' => [
                    'id'                => $product->getData('sku'),
                    'name'              => $product->getData('name'),
                    'taxonomy'          => $this->getTaxonomy(),
                    "currency"          => $this->getCurrency(),
                    "url"               => $this->helperProduct->getProductUrl($product, $shopId),
                    "product_image_url" => $this->imageHelper
                        ->init($product, 'category_page_grid')
                        ->getUrl()
                ]
            ];
        }

        return $config;
    }

    /**
     * get current product
     *
     * @return mixed|null
     */
    private function getCurrentProduct()
    {
        if ($this->registry->registry('current_product')) {
            return $this->registry->registry('current_product');
        }

        return false;
    }

    /**
     * Get taxonomy
     *
     * @return array
     */
    private function getTaxonomy(): array
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
    private function getCurrency(): ?string
    {
        return $this->storeManager->getStore()->getDefaultCurrencyCode();
    }
}
