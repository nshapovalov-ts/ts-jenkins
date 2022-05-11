<?php

/**
 * Retailplace_CatalogSort
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CatalogSort\Model;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Store\Model\Store;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Psr\Log\LoggerInterface;
use Retailplace\CatalogSort\Api\Data\ProductSortScoreAttributesInterface;
use Magento\Framework\DB\Select;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class SortScoreCalculatedManagement
 */
class SortScoreCalculatedManagement
{
    /**
     * @var int
     */
    const BASE_WEIGHTS = 0;

    /**
     * @var int
     */
    const BESTSELLER_WEIGHTS = 1000000;

    /**
     * @var int
     */
    const NEWS_WEIGHTS = 700000;

    /**
     * @var int
     */
    const MARGIN_WEIGHTS = 500000;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * SortScoreCalculatedManagement constructor
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ResourceConnection $resourceConnection
     * @param ProductCollectionFactory $productCollectionFactory
     * @param TimezoneInterface $localeDate
     * @param LoggerInterface $logger
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory,
        TimezoneInterface $localeDate,
        LoggerInterface $logger
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->localeDate = $localeDate;
        $this->logger = $logger;
    }

    /**
     * Calculate Sort Score
     *
     * @return int
     */
    public function updateSortScore(): int
    {
        $products = $this->getProducts();
        $this->updateAttributeValues($products);
        return !empty($products) ? count($products) : 0;
    }

    /**
     * Get product ids to update sort_score
     *
     * @return ProductCollection
     */
    private function getProducts(): ProductCollection
    {
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();

        $todayStartOfDayDate = $this->localeDate->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $todayEndOfDayDate = $this->localeDate->date()->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $productCollection->setStoreId(Store::DEFAULT_STORE_ID);
        $productCollection->addAttributeToSelect('best_seller', 'left');
        $productCollection->addAttributeToSelect('news_from_date', 'left');
        $productCollection->addAttributeToSelect('news_to_date', 'left');

        $productCollection->addAttributeToSelect('retail_margin', 'left');
        $productCollection->addAttributeToSelect('sort_score', 'left');
        $select = $productCollection->getSelect();
        $select->reset(Select::COLUMNS);

        // condition for the definition of new products,
        // the condition is taken from the method \Amasty\Shopby\Model\Layer\Filter\IsNew\Helper::addNewFilter
        $conditionForNew = "(at_news_from_date.value <= '$todayEndOfDayDate') AND (at_news_to_date.value >= '$todayStartOfDayDate') AND (at_news_from_date.value IS NOT NULL OR at_news_to_date.value IS NOT NULL)";

        $oldScoreWeights = "IF(at_sort_score.value IS NOT NULL, FLOOR(at_sort_score.value), 0)";
        $newScoreWeights = "(" . self::BASE_WEIGHTS . "
        + IF(at_best_seller.value = 1, " . self::BESTSELLER_WEIGHTS . ", 0)
        + IF($conditionForNew, " . self::NEWS_WEIGHTS . ", 0)
        + IF(at_retail_margin.value IS NOT NULL, FLOOR(at_retail_margin.value) + " . self::MARGIN_WEIGHTS . ",0))";

        $select->columns([
            'id' => 'e.entity_id',
            'new_score' => $newScoreWeights
        ]);

        $select->where($oldScoreWeights . " != " . $newScoreWeights);

        return $productCollection;
    }

    /**
     * Update 'sort_score' attribute
     *
     * @param ProductCollection $products
     */
    public function updateAttributeValues(ProductCollection $products)
    {
        if (!empty($products) && count($products) > 0) {
            $attribute = $this->getAttributeByCode(ProductSortScoreAttributesInterface::ATTRIBUTE_CODE);
            $insertData = [];
            foreach ($products as $product) {
                $insertData[] = [
                    'attribute_id' => $attribute->getAttributeId(),
                    'store_id'     => Store::DEFAULT_STORE_ID,
                    'entity_id'    => $product->getData('id'),
                    'value'        => $product->getData('new_score')
                ];
            }

            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $attribute->getBackendTable(),
                $insertData
            );
        }
    }

    /**
     * Get Attribute by Code
     *
     * @param string $attributeCode
     * @return AttributeInterface|null
     */
    private function getAttributeByCode(string $attributeCode): ?AttributeInterface
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
}
