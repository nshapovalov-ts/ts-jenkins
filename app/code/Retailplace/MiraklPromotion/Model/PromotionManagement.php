<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Retailplace\MiraklPromotion\Model\Promotion as PromotionModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory as PromotionLinkCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Mirakl\FrontendDemo\Helper\Offer as OfferHelper;
use Mirakl\Core\Model\Shop;

/**
 * Class PromotionManagement
 */
class PromotionManagement
{
    /** @var string */
    public const XML_PATH_CONFIGURABLE_DISPLAY_PROMOTIONS = 'retailplace_mirakl_promotion/promotions_displaying/plp_configurable';
    public const XML_PATH_PROMOTIONS_COUNT = 'retailplace_mirakl_promotion/promotions_displaying/plp_promotions_count';

    /** @var string */
    public const QUOTE_MIRAKL_PROMOTION_DEDUCED_AMOUNT = 'mirakl_promotion_deduced_amount';
    public const QUOTE_MIRAKL_PROMOTION_DATA = 'mirakl_promotion_data';
    public const ORDER_MIRAKL_PROMOTION_DEDUCED_AMOUNT = 'mirakl_promotion_deduced_amount';
    public const ORDER_MIRAKL_PROMOTION_DATA = 'mirakl_promotion_data';

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;

    /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory */
    private $promotionLinkCollectionFactory;

    /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterfaceFactory */
    private $promotionFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var array */
    private $productRelations = [];

    /** @var \Magento\Framework\Serialize\SerializerInterface */
    private $serializer;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory */
    private $dateTimeFactory;

    /** @var \Mirakl\FrontendDemo\Helper\Offer */
    private $offerHelper;

    /**
     * PromotionManagement Constructor
     *
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory $promotionLinkCollectionFactory
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterfaceFactory $promotionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     * @param \Mirakl\FrontendDemo\Helper\Offer $offerHelper
     */
    public function __construct(
        ResourceConnection $resource,
        PromotionLinkCollectionFactory $promotionLinkCollectionFactory,
        PromotionInterfaceFactory $promotionFactory,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        DateTimeFactory $dateTimeFactory,
        OfferHelper $offerHelper
    ) {
        $this->resource = $resource;
        $this->promotionLinkCollectionFactory = $promotionLinkCollectionFactory;
        $this->promotionFactory = $promotionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->offerHelper = $offerHelper;
    }

    /**
     * Get Promotions List by Products List
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[]|array $productsList
     */
    public function getPromotions(array $productsList): array
    {
        $productMapping = [];
        $offerIds = [];
        $this->loadConfigurableRelations($productsList);
        foreach ($productsList as $product) {
            /** @var Shop $shop */
            $shop = $product->getData('shop');
            $shopId = $shop ? (int) $shop->getId() : null;
            $offers = $this->offerHelper->getAllOffers($product, null, $shopId);
            $productSkus[] = $product->getSku();
            if (is_array($offers)) {
                $offerIds = array_merge($offerIds, array_keys($offers));
            }
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                foreach ($this->getConfigurableChildrenSkus((int) $product->getId()) as $simpleSku) {
                    $productMapping[$simpleSku] = $product->getSku();
                }
            }
        }

        return $this->getPromotionsList($offerIds, $productMapping);
    }

    /**
     * Add Mirakl Promotions Data to Quote Items
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee $orderShippingFee
     * @return array
     */
    public function addMiraklPromotionsToQuote(CartInterface $quote, OrderShippingFee $orderShippingFee): array
    {
        $offers = $orderShippingFee->getOffers();
        $quoteItemIds = [];
        if (count($offers)) {
            foreach ($offers as $offer) {
                $items = $quote->getItems() ?: $quote->getAllItems();
                foreach ($items as $quoteItem) {
                    if ($quoteItem->getMiraklOfferId() == $offer->getId() && count($offer->getPromotions())) {
                        /** @var \Mirakl\MMP\Common\Domain\Promotion\AppliedPromotion $promotion */
                        $promotionData = [];
                        $deducedAmount = 0;
                        foreach ($offer->getPromotions() as $promotion) {
                            $promotionData[] = $promotion->toArray();
                            $deducedAmount += $promotion->getDeducedAmount();
                        }

                        $quoteItem->setData(self::QUOTE_MIRAKL_PROMOTION_DEDUCED_AMOUNT, $deducedAmount);
                        $quoteItem->setData(self::QUOTE_MIRAKL_PROMOTION_DATA, $this->serializer->serialize($promotionData));
                        $quoteItemIds[] = $quoteItem->getItemId();
                    }
                }
            }
        }

        return $quoteItemIds;
    }

    /**
     * Remove Promotions from Quote
     *
     * @param int[] $usedItems
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    public function removePromotionsFromQuote(array $usedItems, CartInterface $quote)
    {
        $items = $quote->getItems() ?: $quote->getAllItems();
        foreach ($items as $item) {
            if (!in_array($item->getItemId(), $usedItems)) {
                $item->setData(self::QUOTE_MIRAKL_PROMOTION_DEDUCED_AMOUNT, 0);
                $item->setData(self::QUOTE_MIRAKL_PROMOTION_DATA, '');
            }
        }
    }

    /**
     * Get list of Child Product SKUs for Configurable Product
     *
     * @param int $parentId
     * @return string[]
     */
    private function getConfigurableChildrenSkus(int $parentId): array
    {
        $skuList = [];
        foreach ($this->productRelations as $relation) {
            if ($relation['parent_id'] == $parentId) {
                $skuList[] = $relation['sku'];
            }
        }

        return $skuList;
    }

    /**
     * Get Mapping for Configrable Product Children
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $productsList
     */
    private function loadConfigurableRelations(array $productsList)
    {
        $ids = [];
        foreach ($productsList as $product) {
            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $ids[] = $product->getId();
            }
        }
        if (count($ids)) {
            $this->productRelations = $this->getChildren($ids);
        }
    }

    /**
     * Get Children list for Configurable Product
     *
     * @param int[] $parentIds
     * @return array
     */
    private function getChildren(array $parentIds): array
    {
        $select = $this->resource->getConnection()
            ->select()
            ->from(
                ['l' => $this->resource->getTableName('catalog_product_super_link')],
                ['product_id', 'parent_id']
            )
            ->joinInner(
                ['e' => $this->resource->getTableName('catalog_product_entity')],
                'e.entity_id = l.product_id',
                ['sku']
            )
            ->where(
                'l.parent_id IN (?)',
                $parentIds
            );

        return $this->resource->getConnection()->fetchAll($select);
    }

    /**
     * Get Joined Collection of Offers and Promotions
     *
     * @param int[] $offerIds
     * @return array
     */
    private function getPromotionsCombined(array $offerIds): array
    {
        /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\Collection $collection */
        $collection = $this->promotionLinkCollectionFactory->create();
        $collection->getSelect()
            ->joinInner(
                $this->resource->getTableName(PromotionModel::TABLE_NAME),
                'mirakl_promotion.promotion_id = main_table.promotion_id'
            )
            ->joinInner(
                $this->resource->getTableName('mirakl_offer'),
                'mirakl_offer.offer_id = main_table.offer_id'
            );
        $now = $this->dateTimeFactory->create();
        $collection->addFilterToMap('offer_id', 'main_table.offer_id');
        $collection->addFieldToFilter(OfferInterface::OFFER_ENTITY_ID, ['in' => $offerIds]);
        $collection->addFieldToFilter(PromotionInterface::STATE, PromotionInterface::STATE_ACTIVE);
        $collection->addFieldToFilter(PromotionInterface::START_DATE, ['lteq' => $now->gmtDate()]);
        $collection->addFieldToFilter(
            PromotionInterface::END_DATE,
            [
                ['gteq' => $now->gmtDate()],
                ['null' => true]
            ]
        );

        return $collection->getItems();
    }

    /**
     * Get Promotions array sorted with Product Sku keys
     *
     * @param string[] $offerIds
     * @param array $productMapping
     * @return array
     */
    private function getPromotionsList(array $offerIds, array $productMapping): array
    {
        $promotionsList = [];
        foreach ($this->getPromotionsCombined($offerIds) as $item) {
            $promotion = $this->promotionFactory->create();
            $promotion->setData($item->getData());
            if (isset($productMapping[$item->getProductSku()])) {
                $promotionsList[$productMapping[$item->getProductSku()]][$promotion->getPromotionId()] = $promotion;
            }
            $promotionsList[$item->getProductSku()][$promotion->getPromotionId()] = $promotion;
        }

        return $promotionsList;
    }

    /**
     * Get Visible Promotions by Product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $sellerPromotions
     * @return array
     */
    public function getVisiblePromotionsByProduct(ProductInterface $product, array $sellerPromotions): array
    {
        $result = [];
        if (!empty($sellerPromotions[$product->getSku()])) {
            $visiblePromotionsCount = $this->scopeConfig->getValue(self::XML_PATH_PROMOTIONS_COUNT);
            if ($visiblePromotionsCount) {
                $result = array_slice(
                    $sellerPromotions[$product->getSku()],
                    0,
                    $visiblePromotionsCount,
                    true
                );
            } else {
                $result = $sellerPromotions[$product->getSku()];
            }
        }

        return $this->validateVisiblePromotions($product, $result);
    }

    /**
     * Check Promotions Display Settings
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $result
     * @return array
     */
    private function validateVisiblePromotions(ProductInterface $product, array $result): array
    {
        if ($product->getTypeId() == Configurable::TYPE_CODE
            && !$this->scopeConfig->isSetFlag(self::XML_PATH_CONFIGURABLE_DISPLAY_PROMOTIONS)
        ) {
            $result = [];
        }

        return $result;
    }
}
