<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Block;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Review\Model\Review;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\AttributesVisibilityManagement;
use Retailplace\SmPerformance\Model\SmProductCollector;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Sm\ListingTabs\Block\ListingTabs as SmListingTabs;
use Retailplace\MiraklPromotion\Model\PromotionManagement;

/**
 * Class ListingTabs
 */
class ListingTabs extends SmListingTabs
{
    /** @var \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement */
    private $attributesVisibilityManagement;

    /** @var \Retailplace\MiraklPromotion\Model\PromotionManagement */
    private $promotionManagement;

    /** @var array */
    private $sellerPromotions;

    /** @var array */
    private $productList = [];

    /** @var \Retailplace\SmPerformance\Model\SmProductCollector */
    private $smProductCollector;

    /** @var \Magento\Framework\Data\CollectionFactory */
    private $collectionFactory;

    /**
     * ListingTabs constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Review\Model\Review $review
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Retailplace\ChannelPricing\Model\AttributesVisibilityManagement $attributesVisibilityManagement
     * @param \Retailplace\MiraklPromotion\Model\PromotionManagement $promotionManagement
     * @param \Retailplace\SmPerformance\Model\SmProductCollector $smProductCollector
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param array $data
     * @param null $attr
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ResourceConnection $resource,
        Visibility $catalogProductVisibility,
        Review $review,
        Context $context,
        SerializerJson $jsonSerializer,
        AttributesVisibilityManagement $attributesVisibilityManagement,
        PromotionManagement $promotionManagement,
        SmProductCollector $smProductCollector,
        CollectionFactory $collectionFactory,
        array $data = [],
        $attr = null
    ) {
        parent::__construct(
            $objectManager,
            $resource,
            $catalogProductVisibility,
            $review,
            $context,
            $jsonSerializer,
            $data,
            $attr
        );
        $this->promotionManagement = $promotionManagement;
        $this->attributesVisibilityManagement = $attributesVisibilityManagement;
        $this->smProductCollector = $smProductCollector;
        $this->collectionFactory = $collectionFactory;
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
     * Get promotions by product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getPromotionsByProduct(ProductInterface $product)
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
     * @param ProductInterface $product
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
            $this->sellerPromotions = $this->promotionManagement->getPromotions($this->getProductList());
        }

        return $this->sellerPromotions;
    }

    /**
     * Set Product List
     *
     * @param array $products
     * @return $this
     */
    public function setProductList($products)
    {
        $this->productList = $products;

        return $this;
    }

    /**
     * Get Product List
     *
     * @return array
     */
    public function getProductList()
    {
        return $this->productList;
    }

    /**
     * Get Products from SmProductCollector instead of DB
     *
     * @param null $catids
     * @param false $tab
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|\Magento\Framework\Data\Collection|mixed
     */
    public function _getProductsBasic($catids = null, $tab = false)
    {
        $products = $this->smProductCollector->getProducts();
        $limit = $this->_getConfig('limitation');
        $catids = $catids ?: $this->_getConfig('category_tabs');

        $productCollection = $this->collectionFactory->create();
        foreach ($catids as $catId) {
            if (!empty($products[$catId])) {
                foreach ($products[$catId] as $product) {
                    try {
                        $productCollection->addItem($product);
                    } catch (Exception $e) {
                        $this->_logger->error($e->getMessage());
                    }

                    if (count($productCollection->getItems()) >= $limit) {
                        break;
                    }
                }
            }
        }

        if (!$productCollection->getSize()) {
            $productCollection = parent::_getProductsBasic($catids, $tab);
        }

        return $productCollection;
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
