<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Helper;

use Magento\Catalog\Model\Product;
use Mirakl\Connector\Model\Offer as OfferModel;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Offer extends \Mirakl\FrontendDemo\Helper\Offer
{
    /**
     * RRP array
     * @var array
     */
    private $rrp = [];

    /**
     * @param Product $product
     * @return  bool
     */
    public function isOperatorProductAvailable(Product $product)
    {
        return false;
    }

    /**
     * Get the first item to display
     *
     * @param Product $product
     * @param int|null $shopId
     * @return OfferModel|null
     */
    public function getBestOffer(Product $product, ?int $shopId = null): ?OfferModel
    {
        if ($this->isOperatorProductAvailable($product)) {
            return null;
        }

        $offers = $this->getAllOffers($product, null, $shopId);

        return array_shift($offers);
    }

    /**
     * Get the last item to display
     *
     * @param Product $product
     * @param int|null $shopId
     * @return OfferModel|null
     */
    public function getWorstOffer(Product $product, ?int $shopId = null): ?OfferModel
    {
        if ($this->isOperatorProductAvailable($product)) {
            return null;
        }

        $offers = $this->getAllOffers($product, null, $shopId);

        return array_pop($offers);
    }

    /**
     * Get all offers and collect RRP prices
     *
     * @param Product $product
     * @param int|array $excludeOfferIds
     * @param int|null $shopId
     * @return OfferModel[]
     */
    public function getAllOffers(Product $product, $excludeOfferIds = null, ?int $shopId = null): array
    {
        $offers = parent::getAllOffers($product, $excludeOfferIds);

        if ($shopId) {
            $offers = $this->getShopOffers($offers, $shopId);
        }
        foreach ($offers as $offer) {
            $offerProduct = $offer->getOfferProduct();
            if ($offerProduct) {
                $this->rrp[$product->getId()][] = $offerProduct->getRetailPrice();
            }
        }

        return $offers;
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    public function isConfigurableProduct(ProductInterface $product): bool
    {
        return $product->getTypeId() == Configurable::TYPE_CODE;
    }

    /**
     * Get max retail price
     *
     * @param int $productId
     *
     * @return string|null
     */
    public function getMaxRetailPrice(int $productId): ?string
    {
        $rrp = $this->rrp[$productId] ?? null;
        $maxRrp = '';
        if ($rrp) {
            $maxRrp = max($rrp);
        }

        return $maxRrp;
    }

    /**
     * Get min retail price
     *
     * @param int $productId
     *
     * @return string|null
     */
    public function getMinRetailPrice(int $productId): ?string
    {
        $rrp = $this->rrp[$productId] ?? null;
        $minRrp = '';
        if ($rrp) {
            $minRrp = min($rrp);
        }

        return $minRrp;
    }

    /**
     * @param OfferModel[] $offers
     * @param int $shopId
     * @return OfferModel[]
     */
    public function getShopOffers(array $offers, int $shopId): array
    {
        if ($shopId) {
            foreach ($offers as $key => $offer) {
                if ($offer->getShopId() !== $shopId) {
                    unset($offers[$key]);
                }
            }
        }
        return $offers;
    }
}
