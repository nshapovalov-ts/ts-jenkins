<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Retailplace\ChannelPricing\Api\Data\ProductAttributesInterface;
use Retailplace\ChannelPricing\Model\GroupProcessor\AuPost;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class AuPostExclusiveAttributeUpdater
 */
class AuPostExclusiveAttributeUpdater
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

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AuPostExclusiveAttributeUpdater constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        ShopCollectionFactory $shopCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->offerRepository = $offerRepository;
        $this->productLinkResourceModel = $productLinkResourceModel;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Update Attribute Values for Products
     *
     * @param string[] $skus
     */
    public function update(array $skus)
    {
        $skusFromOffers = $this->getOfferSkus($skus);
        $productIdsFromOffers = $this->getProductIds($skusFromOffers);
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers);
        $this->addAttributeToProducts($productIdsFromOffers);
    }

    /**
     * Set Attribute to 1 for the Products
     *
     * @param int[] $ids
     */
    private function addAttributeToProducts(array $ids)
    {
        $attribute = $this->getAttributeByCode(ProductAttributesInterface::AU_POST_EXCLUSIVE);
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
        $attribute = $this->getAttributeByCode(ProductAttributesInterface::AU_POST_EXCLUSIVE);
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
    private function getOfferSkus(array $skus): array
    {
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(OfferInterface::ACTIVE, 'true');
        $searchCriteriaBuilder->addFilter(OfferInterface::SEGMENT, AuPost::GROUP_CODE, 'finset');
        $searchCriteriaBuilder->addFilter(OfferInterface::SHOP_ID, $this->getShopIds(), 'in');

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

    /**
     * Get all Shop IDs with necessary attributes
     *
     * @return int[]
     */
    private function getShopIds(): array
    {
        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $shopCollection */
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter(ShopInterface::AU_POST_SELLER, ['eq' => 1]);

        return $shopCollection->getAllIds();
    }
}
