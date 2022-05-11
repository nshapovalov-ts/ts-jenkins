<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\Product\AbstractProduct;

/**
 * Class MiraklShopIdCondition
 */
class MiraklShopIdCondition
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param AbstractProduct $subject
     * @return AbstractProduct
     */
    public function afterLoadAttributeOptions(
        AbstractProduct $subject
    ) {
        $attributes = [
            'quote_item_mirakl_shop_id' => __('Supplier Id'),
        ];

        $subject->setAttributeOption(array_merge($subject->getAttributeOption(), $attributes));

        return $subject;
    }

    /**
     * @param AbstractProduct $subject
     * @param AbstractModel $object
     */
    public function beforeValidate(
        AbstractProduct $subject,
        AbstractModel $object
    ) {
        if ($object->getProduct() instanceof Product) {
            /** @var Product $product */
            $product = $object->getProduct();
        } else {
            try {
                $product = $this->productRepository->getById($object->getProductId());
            } catch (NoSuchEntityException $e) {
                $product = null;
            }
        }

        if ($product && $product->getTypeId() !== 'skip') {
            $product->setQuoteItemMiraklShopId($object->getMiraklShopId());
            $object->setProduct($product);
        }
    }
}
