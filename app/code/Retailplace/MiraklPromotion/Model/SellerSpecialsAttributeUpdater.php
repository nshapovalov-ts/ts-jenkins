<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Store\Model\Store;
use Retailplace\MiraklPromotion\Api\Data\ProductAttributesInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory as PromotionLinkCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Class SellerSpecialsAttributeUpdater
 */
class SellerSpecialsAttributeUpdater
{
    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable */
    private $productLinkResourceModel;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory */
    private $dateTimeFactory;

    /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory */
    private $promotionLinkCollectionFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * SellerSpecialsAttributeUpdater constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     * @param \Retailplace\MiraklPromotion\Model\ResourceModel\PromotionLink\CollectionFactory $promotionLinkCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        DateTimeFactory $dateTimeFactory,
        PromotionLinkCollectionFactory $promotionLinkCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->offerRepository = $offerRepository;
        $this->productLinkResourceModel = $productLinkResourceModel;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->promotionLinkCollectionFactory = $promotionLinkCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Update Attribute Values for Products
     *
     * @param string[] $skus
     */
    public function update(array $skus = [])
    {
        $promotionOffers = $this->getOffersWithPromotions();
        $skusFromOffers = $this->getOfferSkus($skus, $promotionOffers);
        $productIdsFromOffers = $this->getProductIds($skusFromOffers);
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers);
        $this->addAttributeToProducts($productIdsFromOffers);
    }

    /**
     * Get Offers Ids with Active Promotions
     *
     * @return int[]
     */
    private function getOffersWithPromotions(): array
    {
        /** @var \Magento\Framework\Stdlib\DateTime\DateTime $now */
        $now = $this->dateTimeFactory->create();

        $promotionLinkCollection = $this->promotionLinkCollectionFactory->create();
        $promotionLinkCollection->joinOffers();
        $promotionLinkCollection->joinPromotions();
        $promotionLinkCollection->addFieldToFilter(PromotionInterface::STATE, PromotionInterface::STATE_ACTIVE);
        $promotionLinkCollection->addFieldToFilter(PromotionInterface::START_DATE, ['lteq' => $now->gmtDate()]);
        $promotionLinkCollection->addFieldToFilter(
            PromotionInterface::END_DATE,
            [
                ['gteq' => $now->gmtDate()],
                ['null' => true]
            ]
        );

        $offerIds = [];
        foreach ($promotionLinkCollection->getItems() as $item) {
            $offerIds[] = $item->getData(OfferInterface::OFFER_ENTITY_ID);
        }

        return $offerIds;
    }

    /**
     * Set Attribute to 1 for the Products
     *
     * @param int[] $ids
     */
    private function addAttributeToProducts(array $ids)
    {
        $attribute = $this->getAttributeByCode(ProductAttributesInterface::SELLER_SPECIALS);
        if ($attribute && count($ids)) {
            $insertData = [];
            foreach ($ids as $id) {
                $insertData[] = [
                    'attribute_id' => $attribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $id,
                    'value' => 1
                ];
            }

            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $attribute->getBackendTable(),
                $insertData
            );
        }
    }

    /**
     * Set Attribute to 0 for the Products
     *
     * @param string[] $allSkus
     * @param int[] $productIdsFromOffers
     */
    private function clearProductsFromAttribute(array $allSkus, array $productIdsFromOffers)
    {
        $attribute = $this->getAttributeByCode(ProductAttributesInterface::SELLER_SPECIALS);
        if ($attribute) {
            $params = [
                'attribute_id = ?' => $attribute->getAttributeId(),
            ];

            if (!empty($allSkus)) {
                $entityIds = $this->getProductIds($allSkus);
                $entityIds = array_diff($entityIds, $productIdsFromOffers);
                $params['entity_id IN (?)'] = $entityIds;
            } else {
                $params['entity_id NOT IN (?)'] = $productIdsFromOffers;
            }

            $this->resourceConnection->getConnection()->update(
                $attribute->getBackendTable(),
                ['value' => 0],
                $params
            );
        }
    }

    /**
     * Get Attribute by Code
     *
     * @param string $attributeCode
     * @return \Magento\Eav\Api\Data\AttributeInterface|null
     */
    private function getAttributeByCode(string $attributeCode): ?AttributeInterface
    {
        $attribute = null;
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                $attributeCode
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $attribute;
    }

    /**
     * Get Products Ids by SKUs list includes Parent Ids for Configurable Products
     *
     * @param string[] $skus
     * @return int[]
     */
    private function getProductIds(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('catalog_product_entity'), ['entity_id'])
            ->where(ProductInterface::SKU . ' IN (?)', $skus);

        $ids = array_keys($connection->fetchAssoc($select));
        if (count($ids)) {
            $configurableIds = $this->getConfigurableProductsByChildren($ids);
            $ids = array_unique(array_merge($ids, $configurableIds));
        }

        return $ids;
    }

    /**
     * Get all Configurable Product IDs by Children IDs
     *
     * @param int[] $ids
     * @return int[]
     */
    private function getConfigurableProductsByChildren(array $ids): array
    {
        return $this->productLinkResourceModel->getParentIdsByChild($ids);
    }

    /**
     * Get Active Offers SKUs
     *
     * @param string[] $skus
     * @return string[]
     */
    private function getOfferSkus(array $skus, array $offerIds): array
    {
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(OfferInterface::ACTIVE, 'true');
        $searchCriteriaBuilder->addFilter(OfferInterface::OFFER_ENTITY_ID, $offerIds, 'in');
        if (count($skus)) {
            $searchCriteriaBuilder->addFilter(OfferInterface::PRODUCT_SKU, $skus, 'in');
        }
        $searchCriteria = $searchCriteriaBuilder->create();

        $offers = $this->offerRepository->getList($searchCriteria);
        $skuList = [];
        foreach ($offers->getItems() as $offer) {
            $skuList[] = $offer->getProductSku();
        }

        return $skuList;
    }
}
