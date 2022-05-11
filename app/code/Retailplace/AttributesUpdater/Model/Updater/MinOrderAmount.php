<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Updater;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Zend_Db_ExprFactory;

/**
 * Class MinOrderAmount
 */
class MinOrderAmount extends AbstractUpdater implements UpdaterInterface
{
    public const MIN_ORDER_AMOUNT_ATTRIBUTE_CODE = 'min_order_amount';

    /** @var string */
    protected $attributeCode = self::MIN_ORDER_AMOUNT_ATTRIBUTE_CODE;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var array */
    private $affectedProductData = [];

    /** @var array */
    private $shopsData = [];

    /**
     * FreeShipping Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        ShopCollectionFactory $shopCollectionFactory,
        Zend_Db_ExprFactory $exprFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $productLinkResourceModel,
            $resourceConnection,
            $attributeRepository,
            $searchCriteriaBuilderFactory,
            $offerRepository,
            $exprFactory,
            $scopeConfig,
            $logger
        );

        $this->shopCollectionFactory = $shopCollectionFactory;
    }

    /**
     * Set Attribute for the Products
     *
     * @param int[] $ids
     * @return array
     */
    protected function addAttributeToProducts(array $ids): array
    {
        $productData = $this->getAffectedProductData();
        $minOrderAmountAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $minAmountData = [];
        $shopsData = $this->getShopsData();

        /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
        foreach ($this->getAffectedOffersList() as $offer) {
            if (!is_array($offer)) {
                $offer = $offer->getData();
            }
            if (!empty($productData[$offer[OfferInterface::PRODUCT_SKU]])
                && isset($shopsData[$offer[OfferInterface::SHOP_ID]])) {
                $productId = $productData[$offer[OfferInterface::PRODUCT_SKU]];
                $minAmountData[$productId][] = $shopsData[$offer[OfferInterface::SHOP_ID]];
            }
        }

        $minAmountInsertData = [];
        foreach ($ids as $productId) {
            if (!empty($minAmountData[$productId])) {
                $minAmountInsertData[] = [
                    'attribute_id' => $minOrderAmountAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $productId,
                    'value' => max($minAmountData[$productId])
                ];
            }
        }

        $this->insertData($minAmountInsertData, $minOrderAmountAttribute->getBackendTable());
        $this->addDataToParents($ids);

        return $minAmountInsertData;
    }

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder->addFilter(OfferInterface::SHOP_ID, $this->getShopIds(), 'in');

        return $searchCriteriaBuilder;
    }

    /**
     * Add Sku field to Select and collect data
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function extendProductIdsSelect(Select $select): Select
    {
        $select->columns('sku');
        $this->affectedProductData = $this->resourceConnection->getConnection()->fetchAll($select);

        return $select;
    }

    protected function getOfferSkus(array $skus): array
    {
        return $this->getOfferSkusAlt($skus);
    }

    protected function extendOffersSelect(Select $select): Select
    {
        $select->where('shop_id IN (?)', $this->getShopIds());

        return $select;
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
        $shopCollection->addFieldToFilter('min-order-amount', ['neq' => 'NULL']);
        $this->shopsData = [];
        foreach ($shopCollection->getItems() as $shop) {
            $this->shopsData[$shop->getId()] = $shop->getData('min-order-amount');
        }

        return $this->convertArrayValuesToInt($shopCollection->getAllIds());
    }

    /**
     * Shops data getter
     *
     * @return array
     */
    private function getShopsData(): array
    {
        return $this->shopsData;
    }

    /**
     * Get Products Data
     *
     * @return array
     */
    private function getAffectedProductData(): array
    {
        $productData = [];
        foreach ($this->affectedProductData as $row) {
            $productData[$row['sku']] = $row['entity_id'];
        }

        return $productData;
    }

    /**
     * Add merged data to Configurable Products
     *
     * @param array $productIds
     */
    private function addDataToParents(array $productIds)
    {
        $minOrderAmountAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $allChildren = $this->getChildrenIdsForConfigurableProducts($productIds, true);
        $productRelationsData = $this->prepareConfigurableRelationsData($allChildren);
        $insertData = $this->collectParentData($productRelationsData);
        $this->insertData($insertData, $minOrderAmountAttribute->getBackendTable());
    }

    /**
     * Prepare Data for Configurable Products depends on Children
     *
     * @param array $productRelationsData
     * @return array
     */
    private function collectParentData(array $productRelationsData): array
    {
        $insertData = [];
        $minOrderAmountAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $childrenIds = array_keys($productRelationsData);
        if (count($productRelationsData)) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection
                ->select()
                ->from(['a' => $minOrderAmountAttribute->getBackendTable()])
                ->where('a.attribute_id = ?', $minOrderAmountAttribute->getAttributeId())
                ->where('a.entity_id IN (?)', $childrenIds)
                ->where('a.store_id = ?', Store::DEFAULT_STORE_ID);
            $attributeData = $connection->fetchAll($select);
            $parentData = [];
            if (count($attributeData)) {
                foreach ($attributeData as $row) {
                    if ($row['value'] && !empty($productRelationsData[$row['entity_id']])) {
                        $parentId = $productRelationsData[$row['entity_id']];
                        if (!empty($parentData[$parentId])) {
                            $parentData[$parentId] = max($parentData[$parentId], $row['value']);
                        } else {
                            $parentData[$parentId] = $row['value'];
                        }
                    }
                }

                foreach ($parentData as $parentId => $data) {
                    $insertData[] = [
                        'attribute_id' => $minOrderAmountAttribute->getAttributeId(),
                        'store_id' => Store::DEFAULT_STORE_ID,
                        'entity_id' => $parentId,
                        'value' => $data
                    ];
                }
            }
        }

        return $insertData;
    }
}
