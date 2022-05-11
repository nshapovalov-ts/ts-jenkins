<?php
/**
 * Retailplace_Wishlist
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Wishlist\Plugin\Helper;

use Magento\Wishlist\Helper\Data as WishlistHelper;
use Magento\Catalog\Model\Product;
use Magento\Wishlist\Model\Item;
use Closure;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\UrlInterface;

/**
 * Class Data
 */
class Data
{

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        UrlInterface $urlBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve params for adding product to wishlist
     *
     * @param WishlistHelper $helper
     * @param Product|Item $item
     * @param array $params
     * @return array
     */
    public function beforeGetAddParams(WishlistHelper $helper, $item, array $params = []): array
    {
        $offer = $item->getData('main_offer');

        if ($offer) {
            $sellerId = $offer->getShopId();
        } else {
            $sellerId = $item->getData('offer_seller_id');
        }

        if ($sellerId) {
            $params['seller_id'] = $sellerId;
        }

        return [$item, $params];
    }

    /**
     * Retrieve URL to item Product
     *
     * @param WishlistHelper $helper
     * @param Closure $proceed
     * @param Item|Product $item
     * @param array $additional
     * @return string
     * @throws LocalizedException
     */
    public function aroundGetProductUrl(
        WishlistHelper $helper,
        Closure $proceed,
        $item,
        array $additional = []
    ): string {
        if ($item instanceof Product) {
            $product = $item;
        } else {
            $product = $item->getProduct();
        }
        $buyRequest = $item->getBuyRequest();
        $fragment = [];
        if (is_object($buyRequest)) {
            $config = $buyRequest->getSuperProductConfig();
            if ($config && !empty($config['product_id'])) {
                $product = $this->productRepository->getById(
                    $config['product_id'],
                    false,
                    $this->storeManager->getStore()->getStoreId()
                );
            }
            $fragment = $buyRequest->getSuperAttribute() ?? [];
            if ($buyRequest->getQty()) {
                $additional['_query']['qty'] = $buyRequest->getQty();
            }
        }

        $sellerId = $item->getData('seller_id');
        if ($sellerId) {
            $url = $this->urlBuilder->getUrl(
                'seller/' . $sellerId . '/' . $product->getUrlKey() . '.html',
                $additional
            );
        } else {
            $url = $product->getUrlModel()->getUrl($product, $additional);
        }

        if ($fragment) {
            $url .= '#' . http_build_query($fragment);
        }

        return $url;
    }

}
