<?php
/**
 * Retailplace_Wishlist
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Wishlist\Plugin\Model;

use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Item
 */
class Item
{
    /**
     * Serializer interface instance.
     *
     * @var Json
     */
    private $serializer;

    public function __construct(
        Json $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Plugin After BeforeSave
     *
     * @param WishlistItem $item
     * @param WishlistItem $self
     * @return WishlistItem
     */
    public function afterBeforeSave(WishlistItem $item, WishlistItem $self): WishlistItem
    {
        $sellerId = $self->getData('seller_id');
        if ($sellerId) {
            return $self;
        }

        $sellerId = $self->getBuyRequest()->getData('seller_id');
        if ($sellerId) {
            $self->setData('seller_id', (int) $sellerId);
        }

        return $self;
    }

    /**
     * Check product representation in item
     *
     * @param WishlistItem $item
     * @param bool $status
     * @param Product $product
     * @return bool
     */
    public function afterRepresentProduct(
        WishlistItem $item,
        bool         $status,
        Product      $product
    ): bool {
        if (!$status) {
            return false;
        }

        $productSellerId = $this->getSellerId($product);
        $sellerId = (int)$item->getData('seller_id');
        if ($productSellerId && $sellerId && $productSellerId !== $sellerId) {
            return false;
        }

        return true;
    }

    /**
     * Get Seller id from custom options
     *
     * @param Product $product
     * @return int|null
     */
    public function getSellerId(Product $product): ?int
    {
        $option = $product->getCustomOption('info_buyRequest');
        $initialData = $option ? $this->serializer->unserialize($option->getValue()) : [];

        if ($initialData['seller_id']) {
            return (int) $initialData['seller_id'];
        }

        return null;
    }
}
