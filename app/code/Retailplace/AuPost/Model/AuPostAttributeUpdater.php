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
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Psr\Log\LoggerInterface;
use Retailplace\AuPost\Api\Data\AttributesInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;

/**
 * Class AuPostAttributeUpdater
 */
class AuPostAttributeUpdater
{
    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable */
    private $productLinkResourceModel;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AuPostAttributeUpdater constructor.
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ProductRepositoryInterface $productRepository,
        ShopCollectionFactory $shopCollectionFactory,
        OfferRepositoryInterface $offerRepository,
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->productRepository = $productRepository;
        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->offerRepository = $offerRepository;
        $this->productLinkResourceModel = $productLinkResourceModel;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Update Attribute Values for Products
     *
     * @param string[] $skus
     */
    public function update(array $skus)
    {
        $shopIds = $this->getShopIds();
        $skusFromOffers = $this->getOfferSkus($shopIds, $skus);
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
        $attribute = $this->getAttributeByCode(AttributesInterface::PRODUCT_AU_POST);
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
        $attribute = $this->getAttributeByCode(AttributesInterface::PRODUCT_AU_POST);
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
     * Get Active Offers SKUs by Shops
     *
     * @param int[] $shopIds
     * @param string[] $skus
     * @return string[]
     */
    private function getOfferSkus(array $shopIds, array $skus): array
    {
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(OfferInterface::SHOP_ID, $shopIds, 'in');
        $searchCriteriaBuilder->addFilter(OfferInterface::ACTIVE, 'true');
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
     * Get all Shop IDs with necessary attribute
     *
     * @return int[]
     */
    private function getShopIds(): array
    {
        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $shopCollection */
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection
            ->addFieldToFilter(ShopInterface::AU_POST_SELLER, ['eq' => true])
            ->addFieldToSelect('id');

        return $shopCollection->getAllIds();
    }
}
