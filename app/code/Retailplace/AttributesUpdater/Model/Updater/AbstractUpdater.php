<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Updater;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Zend_Db_Expr;
use Zend_Db_ExprFactory;

/**
 * Class AbstractUpdater
 */
abstract class AbstractUpdater
{
    /** @var string */
    public const XML_PATH_INSERT_DATA_SIZE = 'retailplace_attribute_updater/data_size/lines_per_insert';

    /** @var string */
    protected $name;

    /** @var string */
    protected $attributeCode;

    /** @var int|string */
    protected $clearedValue = 0;

    /** @var int|string */
    protected $activeValue = 1;

    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable */
    protected $productLinkResourceModel;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    protected $searchCriteriaBuilderFactory;

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    protected $offerRepository;

    /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface[] */
    protected $affectedOffersList = [];

    /** @var \Zend_Db_ExprFactory */
    protected $exprFactory;

    /** @var int[] */
    protected $affectedProductIds;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var array */
    protected $tableFieldTypes = [];

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * AbstractUpdater Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
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
        Zend_Db_ExprFactory $exprFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->productLinkResourceModel = $productLinkResourceModel;
        $this->resourceConnection = $resourceConnection;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->offerRepository = $offerRepository;
        $this->exprFactory = $exprFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Get Updater Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Updater Name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Update Attribute Values for Products
     *
     * @param string[] $skus
     */
    public function run(array $skus = [])
    {
        $skusFromOffers = $this->getOfferSkus($skus);
        $productIdsFromOffers = $this->getProductIds($skusFromOffers);
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers);
        $this->addAttributeToProducts($productIdsFromOffers);
    }

    /**
     * Get Attribute Code
     *
     * @return string
     */
    protected function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    /**
     * Get all Configurable Product IDs by Children IDs
     *
     * @param int[] $ids
     * @param bool $fullData
     * @return array
     */
    protected function getConfigurableProductsByChildren(array $ids, bool $fullData = false): array
    {
        if ($fullData) {
            $data = $this->getParentIdsByChild($ids);
        } else {
            $data = $this->productLinkResourceModel->getParentIdsByChild($ids);
            foreach ($data as $key => $value) {
                $data[$key] = (int) $value;
            }
        }

        return $data;
    }

    /**
     * Get Configurable Products Children Ids
     *
     * @param int[] $parentIds
     * @param bool $fullData
     * @return array
     */
    protected function getChildrenIdsForConfigurableProducts(array $parentIds, bool $fullData = false): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection
            ->select()
            ->from(['l' => $connection->getTableName('catalog_product_super_link')])
            ->where('l.parent_id IN (?)', $parentIds);

        if ($fullData) {
            $data = $connection->fetchAll($select);
        } else {
            $select->columns(['product_id']);
            $data = $connection->fetchCol($select);
        }

        return $data;
    }

    /**
     * Return data in format [simpleProductId => parentProductId]
     *
     * @param array $data
     * @return array
     */
    protected function prepareConfigurableRelationsData(array $data): array
    {
        $result = [];
        foreach ($data as $relation) {
            $result[$relation['product_id']] = $relation['parent_id'];
        }

        return $result;
    }

    /**
     * Set Attribute for the Products
     *
     * @param int[] $ids
     * @return array
     */
    protected function addAttributeToProducts(array $ids): array
    {
        $attribute = $this->getAttributeByCode($this->getAttributeCode());
        $insertData = [];

        if ($attribute && count($ids)) {
            foreach ($ids as $id) {
                $insertData[] = [
                    'attribute_id' => $attribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $id,
                    'value' => $this->getActiveValue()
                ];
            }

            $this->insertData($insertData, $attribute->getBackendTable());
        }

        return $insertData;
    }

    /**
     * Get Attribute by Code
     *
     * @param string $attributeCode
     * @return \Magento\Eav\Api\Data\AttributeInterface|null
     */
    protected function getAttributeByCode(string $attributeCode): ?AttributeInterface
    {
        $attribute = null;
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                $attributeCode
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $attribute;
    }

    /**
     * Set Attribute to 0 for the Products
     *
     * @param string[] $allSkus
     * @param int[] $productIdsFromOffers
     * @param string|null $attributeCode
     */
    protected function clearProductsFromAttribute(
        array $allSkus,
        array $productIdsFromOffers,
        ?string $attributeCode = null
    ) {
        $attribute = $this->getAttributeByCode($attributeCode ?: $this->getAttributeCode());
        if ($attribute) {
            $params = [
                'attribute_id = ?' => $attribute->getAttributeId(),
            ];

            if (!empty($allSkus)) {
                $entityIds = $this->getProductIds($allSkus);
                $entityIds = array_diff($entityIds, $productIdsFromOffers);
                $params['entity_id IN (?)'] = $entityIds;
            } elseif (!empty($productIdsFromOffers)) {
                $params['entity_id NOT IN (?)'] = $productIdsFromOffers;
            }

            $this->resourceConnection->getConnection()->update(
                $attribute->getBackendTable(),
                ['value' => $this->getClearedValue()],
                $params
            );
        }
    }

    /**
     * Get Products Ids by SKUs list includes Parent Ids for Configurable Products
     *
     * @param string[] $skus
     * @return int[]
     */
    protected function getProductIds(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('catalog_product_entity'), ['entity_id'])
            ->where(ProductInterface::SKU . ' IN (?)', $skus);

        $this->extendProductIdsSelect($select);

        $ids = array_keys($connection->fetchAssoc($select));
        if (count($ids)) {
            $configurableIds = $this->getConfigurableProductsByChildren($ids);
            $ids = array_unique(array_merge($ids, $configurableIds));
        }

        $this->affectedProductIds = $ids;

        return $ids;
    }

    /**
     * Product Ids Getter
     *
     * @return int[]
     */
    protected function getAffectedProductIds(): array
    {
        return $this->affectedProductIds;
    }

    /**
     * Extend Select to get Product Ids
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function extendProductIdsSelect(Select $select): Select
    {
        return $select;
    }

    /**
     * Extend Search Criteria Builder and add Filters to get Offers
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        return $searchCriteriaBuilder;
    }

    /**
     * Extend Select and add Filters to get Offers for high performance version
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function extendOffersSelect(Select $select): Select
    {
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
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(OfferInterface::ACTIVE, 'true');

        if (count($skus)) {
            $searchCriteriaBuilder->addFilter(OfferInterface::PRODUCT_SKU, $skus, 'in');
        }

        $this->extendOffersSearchCriteria($searchCriteriaBuilder);

        $searchCriteria = $searchCriteriaBuilder->create();

        $offers = $this->offerRepository->getList($searchCriteria);
        $this->affectedOffersList = $offers->getItems();
        $skuList = [];
        foreach ($offers->getItems() as $offer) {
            $skuList[] = $offer->getProductSku();
        }

        return $skuList;
    }

    /**
     * Get Active Offers SKUs (high performance version)
     *
     * @param string[] $skus
     * @return string[]
     */
    protected function getOfferSkusAlt(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection
            ->select()
            ->from($this->resourceConnection->getTableName('mirakl_offer'))
            ->where('active = ?', 'true');
        if (count($skus)) {
            $select->where('product_sku IN (?)', $skus);
        }

        $this->extendOffersSelect($select);

        $data = $connection->fetchAssoc($select);
        $this->affectedOffersList = $data;
        $skuList = [];
        foreach ($data as $offer) {
            $skuList[] = $offer['product_sku'];
        }

        return $skuList;
    }

    /**
     * Get list of affected Offers
     *
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface[]
     */
    protected function getAffectedOffersList(): array
    {
        return $this->affectedOffersList;
    }

    /**
     * Get Value for attribute clear
     *
     * @return int|string
     */
    protected function getClearedValue()
    {
        if (!$this->clearedValue === null) {
            $this->clearedValue = $this->getDbExpression('null');
        }

        return $this->clearedValue;
    }

    /**
     * Get value for active attribute
     *
     * @return int|string
     */
    protected function getActiveValue()
    {
        return $this->activeValue;
    }

    /**
     * Get Zend Db Expression
     *
     * @param string $expression
     * @return \Zend_Db_Expr
     */
    protected function getDbExpression(string $expression): Zend_Db_Expr
    {
        return $this->exprFactory->create(['expression' => $expression]);
    }

    /**
     * Retrieve parents array by required children
     *
     * @param array $childIds
     * @return array
     */
    protected function getParentIdsByChild(array $childIds): array
    {
        foreach ($childIds as $key => $value) {
            $childIds[$key] = (int) $value;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection
            ->select()
            ->from(['l' => $connection->getTableName('catalog_product_super_link')])
            ->join(
                ['e' => $connection->getTableName('catalog_product_entity')],
                'e.entity_id = l.parent_id',
                []
            )->where('l.product_id IN (?)', $childIds);

        return $connection->fetchAll($select);
    }

    /**
     * Insert Data to DB
     *
     * @param array $data
     * @param string $table
     */
    protected function insertData(array $data, string $table)
    {
        if (count($data)) {
            $data = $this->normalizeData($data, $table);

            $chunkSize = (int) $this->scopeConfig->getValue(self::XML_PATH_INSERT_DATA_SIZE);
            foreach (array_chunk($data, $chunkSize) as $dataChunk) {
                $this->resourceConnection->getConnection()->insertOnDuplicate(
                    $table,
                    $dataChunk
                );
            }
        }
    }

    /**
     * Convert Array values to Int type
     *
     * @param array $data
     * @return array
     */
    protected function convertArrayValuesToInt(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = (int) $value;
        }

        return $data;
    }

    /**
     * Set Data Field Types depends on Table Field Type
     *
     * @param array $data
     * @param string $table
     * @return array
     */
    private function normalizeData(array $data, string $table): array
    {
        $fieldTypes = $this->getTableFieldTypes($table);

        foreach ($data as $rowKey => $row) {
            foreach ($row as $key => $fieldValue) {
                if (!empty($fieldTypes[$key])) {
                    switch ($fieldTypes[$key]) {
                        case 'integer':
                        case 'smallint':
                        case 'bigint':
                        case 'int':
                            $fieldValue = (int) $fieldValue;
                            break;
                        case 'float':
                        case 'decimal':
                            $fieldValue = (float) $fieldValue;
                            break;
                    }

                    $row[$key] = $fieldValue;
                }
            }

            $data[$rowKey] = $row;
        }

        return $data;
    }

    /**
     * Get Table Fields Type
     *
     * @param string $tableName
     * @return array
     */
    private function getTableFieldTypes(string $tableName): array
    {
        if (empty($this->tableFieldTypes[$tableName])) {
            $connection = $this->resourceConnection->getConnection();
            $description = $connection->describeTable($connection->getTableName($tableName));
            $fieldTypes = [];
            foreach ($description as $fieldName => $fieldData) {
                $fieldTypes[$fieldName] = $fieldData['DATA_TYPE'];
            }

            $this->tableFieldTypes[$tableName] = $fieldTypes;
        }

        return $this->tableFieldTypes[$tableName];
    }
}
