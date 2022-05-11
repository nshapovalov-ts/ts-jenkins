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
use Retailplace\MiraklSeller\Block\Seller;
use Zend_Db_ExprFactory;

/**
 * Class MiraklShopIds
 */
class MiraklShopIds extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = Seller::MIRAKL_SHOP_IDS;

    /** @var array */
    private $affectedProductData = [];

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /**
     * MiraklShopIds Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        Zend_Db_ExprFactory $exprFactory,
        ScopeConfigInterface $scopeConfig,
        ShopCollectionFactory $shopCollectionFactory,
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
        $miraklShopIdsAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $shopIdData = [];
        $shopsData = $this->getShopsData();

        /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
        foreach ($this->getAffectedOffersList() as $offer) {
            if (!is_array($offer)) {
                $offer = $offer->getData();
            }
            if (!empty($productData[$offer[OfferInterface::PRODUCT_SKU]])
                && isset($shopsData[$offer[OfferInterface::SHOP_ID]])) {
                $productId = $productData[$offer[OfferInterface::PRODUCT_SKU]];
                $shopIdData[$productId][] = $shopsData[$offer[OfferInterface::SHOP_ID]];
            }
        }

        $shopIdsData = [];
        foreach ($ids as $productId) {
            if (!empty($shopIdData[$productId])) {
                $shopIdsData[] = [
                    'attribute_id' => (int) $miraklShopIdsAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $productId,
                    'value' => implode(',', array_unique($shopIdData[$productId]))
                ];
            }
        }

        $this->insertData($shopIdsData, $miraklShopIdsAttribute->getBackendTable());
        $this->addDataToParents($ids);

        return $shopIdsData;
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

    /**
     * Get Active Offers SKUs
     *
     * @param string[] $skus
     * @return string[]
     */
    protected function getOfferSkus(array $skus): array
    {
        return $this->getOfferSkusAlt($skus);
    }

    /**
     * Get Shops Data
     *
     * @return string[]
     */
    private function getShopsData(): array
    {
        $shopsData = [];

        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $shopCollection */
        $shopCollection = $this->shopCollectionFactory->create();
        foreach ($shopCollection->getItems() as $shop) {
            $shopsData[$shop->getId()] = $shop->getEavOptionId();
        }

        return $shopsData;
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
        $shopIdsAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $allChildren = $this->getChildrenIdsForConfigurableProducts($productIds, true);
        $productRelationsData = $this->prepareConfigurableRelationsData($allChildren);
        $insertData = $this->collectParentData($productRelationsData);
        $this->insertData($insertData, $shopIdsAttribute->getBackendTable());
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
        $shopIdsAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $childrenIds = array_keys($productRelationsData);
        if (count($productRelationsData)) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection
                ->select()
                ->from(['a' => $shopIdsAttribute->getBackendTable()])
                ->where('a.attribute_id = ?', $shopIdsAttribute->getAttributeId())
                ->where('a.entity_id IN (?)', $childrenIds)
                ->where('a.store_id = ?', Store::DEFAULT_STORE_ID);
            $attributeData = $connection->fetchAll($select);
            $parentData = [];
            if (count($attributeData)) {
                foreach ($attributeData as $row) {
                    if ($row['value'] && !empty($productRelationsData[$row['entity_id']])) {
                        $parentId = $productRelationsData[$row['entity_id']];
                        if (!empty($parentData[$parentId])) {
                            $parentData[$parentId] .= ',' . $row['value'];
                        } else {
                            $parentData[$parentId] = $row['value'];
                        }
                    }
                }

                foreach ($parentData as $parentId => $data) {
                    $data = array_filter(explode(',', $data));
                    if (count($data)) {
                        $insertData[] = [
                            'attribute_id' => $shopIdsAttribute->getAttributeId(),
                            'store_id' => Store::DEFAULT_STORE_ID,
                            'entity_id' => $parentId,
                            'value' => implode(',', array_unique($data))
                        ];
                    }
                }
            }
        }

        return $insertData;
    }
}
