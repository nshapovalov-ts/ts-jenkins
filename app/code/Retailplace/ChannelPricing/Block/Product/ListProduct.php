<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ListProduct as MagentoListProduct;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Retailplace\MiraklPromotion\Model\PromotionManagement;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class ListProduct
 */
class ListProduct extends MagentoListProduct
{
    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /** @var \Retailplace\MiraklPromotion\Model\PromotionManagement*/
    private $promotionManagement;

    /** @var array */
    private $sellerPromotions;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /**
     * ListProduct constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     * @param \Retailplace\MiraklPromotion\Model\PromotionManagement $promotionManagement
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        PromotionManagement $promotionManagement,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );

        $this->promotionManagement = $promotionManagement;
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
        $this->timezone = $timezone;
    }

    /**
     * Get promotions by product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getPromotionsByProduct($product): array
    {
        $sellerPromotions = $this->getSellerPromotions();

        return $sellerPromotions[$product->getSku()] ?? [];
    }

    /**
     * Get visible promotions by product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getVisiblePromotionsByProduct($product): array
    {
        $sellerPromotions = $this->getSellerPromotions();
        $result = $this->promotionManagement->getVisiblePromotionsByProduct($product, $sellerPromotions);

        return $result;
    }

    /**
     * Check is promotion block visible
     *
     * @param ProductInterface $product
     */
    public function isPromotionsBlockVisible($product)
    {
        $result = true;
        if (!count($this->getPromotionsByProduct($product))) {
            $result = false;
        }

        return $result;
    }

    /**
     * Get seller promotions
     *
     * @return array
     */
    private function getSellerPromotions(): array
    {
        if ($this->sellerPromotions === null) {
            $this->sellerPromotions = $this->promotionManagement->getPromotions($this->_getProductCollection()->getItems());
        }

        return $this->sellerPromotions;
    }

    /**
     * Check if we can show the attribute
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productIsAuPostExclusive(ProductInterface $product): bool
    {
        $isAttributeVisible = $this->attributesVisibilityManagement
            ->checkAttributeVisibility(ProductAttributesInterface::AU_POST_EXCLUSIVE);

        return $isAttributeVisible && $product->getData(ProductAttributesInterface::AU_POST_EXCLUSIVE);
    }

    /**
     * Check if we can show the attribute
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productIsNlnaExclusive(ProductInterface $product): bool
    {
        $isAttributeVisible = $this->attributesVisibilityManagement
            ->checkAttributeVisibility(ProductAttributesInterface::NLNA_EXCLUSIVE);

        return $isAttributeVisible && $product->getData(ProductAttributesInterface::NLNA_EXCLUSIVE);
    }

    /**
     * Get Attribute Label
     *
     * @return string
     */
    public function getAuPostExclusiveLabel()
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(ProductAttributesInterface::AU_POST_EXCLUSIVE);
    }

    /**
     * Get Attribute Label
     *
     * @return string
     */
    public function getNlnaExclusiveLabel()
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(ProductAttributesInterface::NLNA_EXCLUSIVE);
    }

    /**
     * Check Attribute Value
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productIsOpenDuringXmas(ProductInterface $product): bool
    {
        return (bool) $product->getData(SellerTagsAttributes::PRODUCT_OPEN_DURING_XMAS);
    }

    /**
     * Get Attribute Label
     *
     * @return string
     */
    public function getOpenDuringXmasLabel(): string
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(SellerTagsAttributes::PRODUCT_OPEN_DURING_XMAS);
    }

    /**
     * Check Attribute Value
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function productShopIsClosed(ProductInterface $product): bool
    {
        return (bool) $product->getData(SellerTagsAttributes::PRODUCT_CLOSED_TO);
    }

    /**
     * Get Attribute Label
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return string
     */
    public function getClosedShopLabel(ProductInterface $product): string
    {
        $label = $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(SellerTagsAttributes::PRODUCT_CLOSED_TO);
        $date = $this->timezone->date(
            strtotime($product->getData(SellerTagsAttributes::PRODUCT_CLOSED_TO))
        )->format('d/m');

        return sprintf($label .' %s', $date);
    }
}
