<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */
declare(strict_types=1);

namespace Retailplace\MiraklConnector\Model;

use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Zend_Db_Expr;
use Exception;
use Psr\Log\LoggerInterface;
use Mirakl\Core\Helper\Data;
use Zend_Db_Select_Exception;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\Product;

/**
 * Class MarginUpdater used for importing
 * retail margin and updating for trade attribute.
 */
class MarginUpdater
{
    /**
     *  Attribute Code
     * @var string
     */
    const RETAIL_MARGIN = "retail_margin";

    /**
     *  Attribute Code
     * @var string
     */
    const IS_FOR_TRADE = 'is_businesses';

    /**
     *  Attribute Code
     * @var string
     */
    const RETAIL_PRICE = 'retail_price';

    /**
     *  Group Seperators for price range from mirakl table used in mysql query
     * @var string
     */
    const GROUP_CONCAT_SEPARATORS = "||";

    /**
     *  Range Seperators for price range from mirakl table
     * @var string
     */
    const RANGE_SEPARATORS = '|';

    /**
     *  Comma Seperator
     * @var string
     */
    const COMA_SEPARATORS = ",";

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Data
     */
    private $miraklCoreHelper;

    /**
     * MarginUpdater constructor.
     *
     * @param ResourceConnection $resource
     * @param AttributeRepository $attributeRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     * @param Data $miraklCoreHelper
     */
    public function __construct(
        ResourceConnection $resource,
        AttributeRepository $attributeRepository,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger,
        Data $miraklCoreHelper
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->attributeRepository = $attributeRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->miraklCoreHelper = $miraklCoreHelper;
    }

    /**
     * Update retail margin attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param array $skus
     * @return  void
     */
    public function updateRetailMarginAttribute(array $skus = []): void
    {
        try {
            $isEnterprise = $this->miraklCoreHelper->isEnterprise();
            $forTradeMarginThreshold = 40;
            $entityCol = $isEnterprise ? 'row_id' : 'entity_id';

            $entityIds = []; // Will be used to filter entity ids to update

            if (!empty($skus)) {
                $select = $this->connection->select()
                    ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                    ->where('sku IN (?)', $skus);
                $entityIds = $this->connection->fetchCol($select);

                if (empty($entityIds)) {
                    return; // Do not do anything if we cannot find any products with given skus
                }
            }

            $retailMarginAttribute = $this->getAttribute(self::RETAIL_MARGIN);
            $isForTradeAttribute = $this->getAttribute(self::IS_FOR_TRADE);

            // Reset all values of this retail attribute
            $this->resetAttributeValues($retailMarginAttribute, $entityCol, $entityIds);
            // Reset all values of this For trade attribute
            $this->resetAttributeValues($isForTradeAttribute, $entityCol, $entityIds);

            $insertMarginData = [];
            $insertIsForTradeData = [];

            if ($skus) {
                $productCollection = $this->getSimpleProductCollection($entityCol, $entityIds, $isEnterprise);
                /** @var $product Product */
                foreach ($productCollection as $product) {
                    $margin = $this->getMargin($product);
                    $insertMarginData[] = [
                        'attribute_id' => $retailMarginAttribute->getAttributeId(),
                        'store_id'     => Store::DEFAULT_STORE_ID,
                        $entityCol     => $product->getId(),
                        'value'        => $margin,
                    ];

                    $insertIsForTradeData[] = [
                        'attribute_id' => $isForTradeAttribute->getAttributeId(),
                        'store_id'     => Store::DEFAULT_STORE_ID,
                        $entityCol     => $product->getId(),
                        'value'        => $margin < $forTradeMarginThreshold ? 1 : 0,
                    ];
                }

                $configurableProductCollection = $this->productCollectionFactory->create();
                $configurableProductCollection->addAttributeToFilter('type_id', 'configurable');
                $configurableProductSql = $configurableProductCollection->getSelect()
                    ->join(
                        ['cpsl' => 'catalog_product_super_link'],
                        '`cpsl`.`parent_id` = `e`.`entity_id`',
                        ''
                    );
                if (!empty($entityIds)) {
                    $configurableProductSql->where("cpsl.product_id IN (?)", $entityIds);
                }

                $configurableSelectionsIds = $this->connection->fetchPairs(
                    (clone $configurableProductSql)
                        ->reset(Select::COLUMNS)
                        ->columns(['cpsl.product_id', 'entity_id'])
                );
                $configurableEntityIds = array_unique(array_values($configurableSelectionsIds));
                $configurableSimpleEntityIds = array_unique(array_keys($configurableSelectionsIds));
                $simpleProductCollection = $this->getSimpleProductCollection($entityCol, $configurableSimpleEntityIds, $isEnterprise);
                if (!empty($configurableEntityIds)) {
                    $this->resetAttributeValues($retailMarginAttribute, $entityCol, $configurableEntityIds);
                    $this->resetAttributeValues($isForTradeAttribute, $entityCol, $configurableEntityIds);

                    $configurableProductCollection->getSelect()->join(
                        ['o' => $this->getTableName('mirakl_offer')],
                        'o.entity_id = cpsl.product_id AND o.active = "true" AND (o.segment = "" or o.segment IS NULL)',
                        [
                            'simple_ids' => new Zend_Db_Expr('GROUP_CONCAT(cpsl.product_id)'),
                        ]
                    )->group('cpsl.parent_id');

                    if ($isEnterprise) {
                        $configurableProductCollection->getSelect()->setPart('disable_staging_preview', true);
                        $configurableProductCollection->getSelect()->group('e.row_id');
                    }
                    /** @var $configurableProduct Product */
                    foreach ($configurableProductCollection as $configurableProduct) {
                        $simpleIds = explode(self::COMA_SEPARATORS, $configurableProduct->getData('simple_ids'));
                        $margin = 0;
                        foreach ($simpleIds as $simpleId) {
                            $product = $simpleProductCollection->getItemById($simpleId);
                            $newMargin = $this->getMargin($product);
                            if ($newMargin > $margin) {
                                $margin = $newMargin;
                            }
                        }
                        $insertMarginData[] = [
                            'attribute_id' => $retailMarginAttribute->getAttributeId(),
                            'store_id'     => Store::DEFAULT_STORE_ID,
                            $entityCol     => $configurableProduct->getId(),
                            'value'        => $margin,
                        ];

                        $insertIsForTradeData[] = [
                            'attribute_id' => $isForTradeAttribute->getAttributeId(),
                            'store_id'     => Store::DEFAULT_STORE_ID,
                            $entityCol     => $configurableProduct->getId(),
                            'value'        => $margin < $forTradeMarginThreshold ? 1 : 0,
                        ];
                    }
                }
            }

            if ($insertMarginData) {
                $this->connection->insertOnDuplicate(
                    $retailMarginAttribute->getBackendTable(),
                    $insertMarginData
                );
            }
            if ($insertIsForTradeData) {
                $this->connection->insertOnDuplicate(
                    $isForTradeAttribute->getBackendTable(),
                    $insertIsForTradeData
                );
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @param string $tableName
     * @return  string
     */
    private function getTableName(string $tableName): string
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * @param string $attrCode
     * @return ProductAttributeInterface|AttributeInterface
     * @throws  Exception
     * @throws NoSuchEntityException
     */
    private function getAttribute(string $attrCode)
    {
        return $this->attributeRepository->get($attrCode);
    }

    /**
     * @param AttributeInterface $attribute
     * @param string $entityCol
     * @param array $entityIds
     */
    private function resetAttributeValues(AttributeInterface $attribute, string $entityCol, array $entityIds)
    {
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);
    }

    /**
     * @param string $entityCol
     * @param array $entityIds
     * @param bool $isEnterprise
     * @return Collection
     * @throws Zend_Db_Select_Exception
     */
    private function getSimpleProductCollection(string $entityCol, array $entityIds, bool $isEnterprise): Collection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('type_id', 'simple');
        $productCollection->addAttributeToSelect(self::RETAIL_PRICE, 'left');
        $productCollection->getSelect()
            ->join(
                ['o' => $this->getTableName('mirakl_offer')],
                'o.entity_id = e.entity_id AND o.active = "true" AND (o.segment = "" or o.segment IS NULL)',
                [
                    'lowest_offer_price'     => new Zend_Db_Expr('MIN(o.price)'),
                    'simple_price_ranges'    =>
                        new Zend_Db_Expr(
                            'GROUP_CONCAT(price_ranges SEPARATOR "' . self::GROUP_CONCAT_SEPARATORS . '")'
                        ),
                    'simple_discount_ranges' =>
                        new Zend_Db_Expr(
                            'GROUP_CONCAT(discount_ranges SEPARATOR "' . self::GROUP_CONCAT_SEPARATORS . '")'
                        ),
                ]
            )
            ->group('e.entity_id');
        if ($isEnterprise) {
            $productCollection->getSelect()->setPart('disable_staging_preview', true);
            $productCollection->getSelect()->group('e.row_id');
        }
        if (!empty($entityIds)) {
            $productCollection->getSelect()->where("e.$entityCol IN (?)", $entityIds);
        }
        return $productCollection;
    }

    /**
     * @param Product $product
     * @return float|int
     */
    private function getMargin(Product $product)
    {
        $retailPrice = $product->getData(self::RETAIL_PRICE);
        $offerPrice = $product->getData('lowest_offer_price');
        $simplePriceRanges = explode(self::GROUP_CONCAT_SEPARATORS, $product->getData('simple_price_ranges'));
        foreach ($simplePriceRanges as $rangesString) {
            if (strpos($rangesString, self::COMA_SEPARATORS) !== false && $rangesString) {
                foreach (explode(self::COMA_SEPARATORS, $rangesString) as $range) {
                    list($qty, $price) = explode(self::RANGE_SEPARATORS, $range);
                    if ($price < $offerPrice) {
                        $offerPrice = $price;
                    }
                }
            }
        }
        $simpleDiscountRanges = explode(self::GROUP_CONCAT_SEPARATORS, $product->getData('simple_discount_ranges'));
        foreach ($simpleDiscountRanges as $discountRangesString) {
            if (strpos($discountRangesString, self::COMA_SEPARATORS) !== false && $discountRangesString) {
                foreach (explode(self::COMA_SEPARATORS, $discountRangesString) as $range) {
                    list($qty, $price) = explode(self::RANGE_SEPARATORS, $range);
                    if ($price < $offerPrice) {
                        $offerPrice = $price;
                    }
                }
            }   
        }
        $margin = 0;
        if ($retailPrice > 0 && $offerPrice) {
            $margin = round(((($retailPrice - $offerPrice) / $retailPrice) * 100));
        }
        return $margin;
    }
}
