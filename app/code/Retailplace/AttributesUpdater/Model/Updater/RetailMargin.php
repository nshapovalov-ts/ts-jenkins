<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Updater;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Setup\Declaration\Schema\Db\MySQL\Definition\Columns\Timestamp;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Zend_Db_ExprFactory;

/**
 * Class RetailMargin
 */
class RetailMargin extends AbstractUpdater implements UpdaterInterface
{
    /** @var int */
    public const COLLECTION_CHUNK_SIZE = 100000;
    public const IS_BUSINESS_MARGIN_THRESHOLD = 40;

    /** @var string */
    public const IS_BUSINESS_ATTRIBUTE = 'is_businesses';

    /** @var string */
    public const RETAIL_PRICE = 'retail_price';

    /** @var string */
    protected $attributeCode = 'retail_margin';

    /** @var int */
    protected $clearedValue = 0;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /** @var array */
    private $mappedOffersData;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /**
     * RetailMargin Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
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
        ProductCollectionFactory $productCollectionFactory,
        DateTime $dateTime,
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

        $this->productCollectionFactory = $productCollectionFactory;
        $this->dateTime = $dateTime;
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
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers, $this->getAttributeCode());
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers, self::IS_BUSINESS_ATTRIBUTE);
        $insertData = $this->addAttributeToProducts($productIdsFromOffers);
        $this->addDataToParents($productIdsFromOffers);
    }

    /**
     * Set Attribute for the Products
     *
     * @param int[] $ids
     * @return array
     */
    protected function addAttributeToProducts(array $ids): array
    {
        $marginInsertData = [];
        $isBusinessInsertData = [];

        $marginAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $isBusinessAttribute = $this->getAttributeByCode(self::IS_BUSINESS_ATTRIBUTE);

        foreach (array_chunk($ids, self::COLLECTION_CHUNK_SIZE) as $idsChunk) {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter(ProductInterface::TYPE_ID, Type::TYPE_SIMPLE);
            $productCollection->addAttributeToSelect(self::RETAIL_PRICE, 'left');
            $productCollection->addFieldToFilter('entity_id', ['in' => $idsChunk]);

            /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
            foreach ($productCollection->getItems() as $product) {
                $margin = $this->getProductMargin($product);
                $marginInsertData[] = [
                    'attribute_id' => $marginAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $product->getId(),
                    'value' => $margin
                ];

                $isBusinessInsertData[] = [
                    'attribute_id' => $isBusinessAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $product->getId(),
                    'value' => $margin < self::IS_BUSINESS_MARGIN_THRESHOLD
                ];
            }
        }

        if (count($ids) && $isBusinessAttribute && $marginAttribute) {
            $this->insertData($marginInsertData, $marginAttribute->getBackendTable());
            $this->insertData($isBusinessInsertData, $isBusinessAttribute->getBackendTable());
        }

        return [
            'margin' => $marginInsertData,
            'is_business' => $isBusinessInsertData
        ];
    }

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder
            ->addFilter(OfferInterface::SEGMENT, '');

        return $searchCriteriaBuilder;
    }

    /**
     * Add data to Configurable Products
     *
     * @param array $productIds
     */
    private function addDataToParents(array $productIds)
    {
        $marginAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $isBusinessAttribute = $this->getAttributeByCode(self::IS_BUSINESS_ATTRIBUTE);

        $allChildren = $this->getChildrenIdsForConfigurableProducts($productIds, true);
        $productRelationsData = $this->prepareConfigurableRelationsData($allChildren);
        $insertData = $this->collectParentData($productRelationsData);

        $this->insertData($insertData['margin'], $marginAttribute->getBackendTable());
        $this->insertData($insertData['is_business'], $isBusinessAttribute->getBackendTable());
    }

    /**
     * Generate Margin by Product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return int
     */
    private function getProductMargin(ProductInterface $product): int
    {
        $retailPrice = $product->getData(self::RETAIL_PRICE);
        $offerPrice = null;
        $margin = 0;

        $offersData = $this->collectOffersData();
        if (isset($offersData[$product->getSku()])) {
            /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
            foreach ($offersData[$product->getSku()] as $offer) {
                $offerPrice = $offer->getPrice();

                $priceRanges = $offer->getPriceRanges();
                if (count($priceRanges->getItems())) {
                    foreach ($priceRanges->getItems() as $rangeItem) {
                        $rangeItem = $rangeItem->toArray();
                        $offerPrice = min($offerPrice, $rangeItem['price']);
                    }
                }

                if ($this->isOfferDiscountActive($offer)) {
                    $discountRanges = $offer->getDiscount();
                    if ($discountRanges->getRanges()) {
                        foreach ($discountRanges->getRanges()->getItems() as $rangeItem) {
                            $rangeItem = $rangeItem->toArray();
                            $offerPrice = min($offerPrice, $rangeItem['price']);
                        }
                    }
                }
            }
        }

        if ($offerPrice && is_numeric($retailPrice) && $retailPrice > 0) {
            $margin = round((($retailPrice - $offerPrice) / $retailPrice) * 100);
        }

        return (int) $margin;
    }

    /**
     * Normalize Offers Data
     *
     * @return array
     */
    private function collectOffersData(): array
    {
        if (!$this->mappedOffersData) {
            $this->mappedOffersData = [];
            foreach ($this->getAffectedOffersList() as $offer) {
                $this->mappedOffersData[$offer->getProductSku()][] = $offer;
            }
        }

        return $this->mappedOffersData;
    }

    /**
     * Check if Offer Discount Price is Active
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer
     * @return bool
     */
    private function isOfferDiscountActive(OfferInterface $offer): bool
    {
        $now = $this->dateTime->gmtDate(Mysql::TIMESTAMP_FORMAT);
        $startDate = $offer->getDiscountStartDate();
        if (!$startDate || $startDate == Timestamp::CONST_DEFAULT_TIMESTAMP) {
            $startDateValid = true;
        } else {
            $startDateValid = $startDate >= $now;
        }

        $endDate = $offer->getDiscountEndDate();
        if (!$endDate || $endDate == Timestamp::CONST_DEFAULT_TIMESTAMP) {
            $endDateValid = true;
        } else {
            $endDateValid = $endDate < $now;
        }

        return $startDateValid && $endDateValid;
    }

    /**
     * Prepare Data for Configurable Products depends on Children
     *
     * @param array $productRelationsData
     * @return array
     */
    private function collectParentData(array $productRelationsData): array
    {
        $insertData = [
            'margin' => [],
            'is_business' => []
        ];
        $marginAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $isBusinessAttribute = $this->getAttributeByCode(self::IS_BUSINESS_ATTRIBUTE);

        $childrenIds = array_keys($productRelationsData);
        if (count($productRelationsData)) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection
                ->select()
                ->from(['a' => $marginAttribute->getBackendTable()])
                ->where('a.attribute_id = ?', $marginAttribute->getAttributeId())
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
                    $insertData['margin'][] = [
                        'attribute_id' => $marginAttribute->getAttributeId(),
                        'store_id' => Store::DEFAULT_STORE_ID,
                        'entity_id' => $parentId,
                        'value' => $data
                    ];

                    $insertData['is_business'][] = [
                        'attribute_id' => $isBusinessAttribute->getAttributeId(),
                        'store_id' => Store::DEFAULT_STORE_ID,
                        'entity_id' => $parentId,
                        'value' => $data < self::IS_BUSINESS_MARGIN_THRESHOLD
                    ];
                }
            }
        }

        return $insertData;
    }
}
