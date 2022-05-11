<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Block\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\View as MagentoProductView;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Magento\Catalog\Block\Product\Context;
use Magento\Framework\Json\EncoderInterface as JsonEncoderInterface;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;

/**
 * Class View
 */
class View extends MagentoProductView
{
    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /**
     * View constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $urlEncoder,
        JsonEncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
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
}
