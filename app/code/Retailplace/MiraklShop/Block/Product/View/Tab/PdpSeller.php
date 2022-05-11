<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Block\Product\View\Tab;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Context;
use Retailplace\MiraklShop\Block\Product\ListProductWithoutFilters;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Url\Helper\Data;
use Mirakl\Core\Model\Shop;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\Core\Model\ResourceModel\Shop as ShopResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Retailplace\MiraklPromotion\Model\PromotionManagement;
use Retailplace\MiraklSeller\Helper\Data as SellerHelper;
use Retailplace\CustomerAccount\Helper\ApprovalContext;
use Retailplace\MiraklFrontendDemo\Helper\Data as MiraklFrontendDemoHelper;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Sm\Market\Helper\Data as SmHelper;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class PdpSeller
 */
class PdpSeller extends ListProductWithoutFilters implements IdentityInterface
{
    /** @var int */
    public const SELLER_PRODUCTS_COUNT = 8;

    /** @var \Mirakl\Core\Model\ShopFactory */
    private $miraklShopFactory;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop */
    private $shopResourceModel;

    /** @var \Mirakl\Core\Model\Shop */
    private $shop;

    /** @var \Retailplace\MiraklSeller\Helper\Data */
    private $sellerHelper;

    /** @var \Retailplace\CustomerAccount\Helper\ApprovalContext */
    private $approvalContext;

    /** @var \Retailplace\MiraklFrontendDemo\Helper\Data */
    private $miraklFrontendDemoHelper;

    /** @var \Sm\Market\Helper\Data */
    private $smHelper;

    /** @var \Magento\Catalog\Helper\Output */
    private $outputHelper;

    /** @var \Retailplace\MiraklPromotion\Model\PromotionManagement */
    private $promotionManagement;

    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /** @var array */
    private $sellerPromotions;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /**
     * PdpSeller Constructor
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Mirakl\Core\Model\ShopFactory $miraklShopFactory
     * @param \Mirakl\Core\Model\ResourceModel\Shop $shopResourceModel
     * @param \Retailplace\MiraklSeller\Helper\Data $sellerHelper
     * @param \Retailplace\CustomerAccount\Helper\ApprovalContext $approvalContext
     * @param \Retailplace\MiraklFrontendDemo\Helper\Data $miraklFrontendDemoHelper
     * @param \Sm\Market\Helper\Data $smHelper
     * @param \Magento\Catalog\Helper\Output $outputHelper
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
        ShopFactory $miraklShopFactory,
        ShopResourceModel $shopResourceModel,
        SellerHelper $sellerHelper,
        ApprovalContext $approvalContext,
        MiraklFrontendDemoHelper $miraklFrontendDemoHelper,
        SmHelper $smHelper,
        OutputHelper $outputHelper,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        PromotionManagement $promotionManagement,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);

        $this->miraklShopFactory = $miraklShopFactory;
        $this->shopResourceModel = $shopResourceModel;
        $this->sellerHelper = $sellerHelper;
        $this->approvalContext = $approvalContext;
        $this->miraklFrontendDemoHelper = $miraklFrontendDemoHelper;
        $this->smHelper = $smHelper;
        $this->outputHelper = $outputHelper;
        $this->promotionManagement = $promotionManagement;
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
        $this->timezone = $timezone;
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return ['pdp_seller_products_block'];
    }

    /**
     * Get Shop by Product
     *
     * @return \Mirakl\Core\Model\Shop
     */
    public function getShop(): Shop
    {
        if (!$this->shop) {
            if ($shop = $this->getProduct()->getShop()) {
                $this->shop = $shop;
            } else {
                $shopId = $this->getProduct()->getData('mirakl_shop_ids');
                $this->shop = $this->miraklShopFactory->create();
                $this->shopResourceModel->load($this->shop, $shopId, 'eav_option_id');
            }
        }

        return $this->shop;
    }

    /**
     * Get Products Collection for Shop
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection(): ProductCollection
    {
       return $this->getLoadedProductCollection();
    }

    /**
     * Check Customer Auth State
     *
     * @return bool
     */
    public function checkIsCustomerLoggedIn(): bool
    {
        return (bool) $this->sellerHelper->checkIsCustomerLoggedIn();
    }

    /**
     * Check Is Product New
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function isProductNew(ProductInterface $product): bool
    {
        return $this->sellerHelper->isProductNew($product);
    }

    /**
     * Format Price
     *
     * @param string $price
     * @return string
     */
    public function formatPrice(string $price): string
    {
        return (string) $this->sellerHelper->formatPrice($price);
    }

    /**
     * Check is Approval
     *
     * @return int
     */
    public function checkIsApproval(): int
    {
        return (int) $this->approvalContext->checkIsApproval();
    }

    /**
     * Calculate Margin
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getCalculatedMargin(ProductInterface $product): string
    {
        return $this->miraklFrontendDemoHelper->getCalculatedMargin($product);
    }

    /**
     * Get Sm Config from Advanced Section
     *
     * @param string $name
     * @return string|null
     */
    public function getSmAdvancedConfig(string $name): ?string
    {
        return $this->smHelper->getAdvanced($name);
    }

    /**
     * Get Media Url for Sm Module
     *
     * @return string|null
     */
    public function getSmMediaUrl(): ?string
    {
        return $this->smHelper->getMediaUrl();
    }

    /**
     * Get Product Attribute Value
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param string $attributeHtml
     * @param string $attributeName
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function productAttribute(ProductInterface $product, string $attributeHtml, string $attributeName): string
    {
        return $this->outputHelper->productAttribute($product, $attributeHtml, $attributeName);
    }

    /**
     * Get Minimum Qty Html
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param int $minimum
     * @return string
     */
    public function getMinimumQtyHtml(ProductInterface $product, int $minimum): string
    {
        return $this->miraklFrontendDemoHelper->getMinimumQtyHtml($product, $minimum);
    }

    /**
     * Get promotions by product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getPromotionsByProduct(ProductInterface $product): array
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
    public function getVisiblePromotionsByProduct(ProductInterface $product): array
    {
        $sellerPromotions = $this->getSellerPromotions();
        $result = $this->promotionManagement->getVisiblePromotionsByProduct($product, $sellerPromotions);

        return $result;
    }

    /**
     * Check is promotion block visible
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    public function isPromotionsBlockVisible(ProductInterface $product): bool
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
            $this->sellerPromotions = $this->promotionManagement->getPromotions($this->getProductCollection()->getItems());
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
    public function getAuPostExclusiveLabel(): string
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(ProductAttributesInterface::AU_POST_EXCLUSIVE);
    }

    /**
     * Get Attribute Label
     *
     * @return string
     */
    public function getNlnaExclusiveLabel(): string
    {
        return $this->attributesVisibilityManagement
            ->getAttributeLabelByCode(ProductAttributesInterface::NLNA_EXCLUSIVE);
    }

    /**
     * Override to replace Category Id to Root Category
     *
     * @param $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        try {
            $rootCategory = $this->_storeManager->getStore()->getRootCategoryId();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            $rootCategory = 0;
        }

        return parent::setCategoryId($rootCategory);
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

    /**
     * Get Product Collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getProductCollection()
    {
        $collection = parent::_getProductCollection();

        if (!$collection->isLoaded()) {
            $shopId = $this->getShop()->getEavOptionId();
            $collection->addFieldToFilter('mirakl_shop_ids', $shopId);
            $collection->setFlag('has_shop_ids_filter', true);
            $collection->setPageSize(self::SELLER_PRODUCTS_COUNT);
        }

        return $collection;
    }
}
