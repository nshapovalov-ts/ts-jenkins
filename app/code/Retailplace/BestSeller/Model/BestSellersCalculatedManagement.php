<?php

/**
 * Retailplace_BestSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\BestSeller\Model;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Store\Model\Store;
use Zend_Db_ExprFactory;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\ResourceConnectionFactory;
use Magento\Framework\App\ResourceConnection;
use Retailplace\BestSeller\Api\Data\ProductBestSellerAttributesInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Psr\Log\LoggerInterface;

/**
 * Class BestSellersCalculatedManagement
 */
class BestSellersCalculatedManagement
{
    /** @var string */
    public const XML_PATH_BEST_SELLERS_CALCULATED_PURCHASE_PERIOD = 'retailplace_best_sellers/best_sellers_update/purchase_period';
    public const XML_PATH_BEST_SELLERS_CALCULATED_PURCHASE_LIMIT = 'retailplace_best_sellers/best_sellers_update/purchase_limit';
    public const XML_PATH_BEST_SELLERS_CALCULATED_CATEGORY_ID = 'retailplace_best_sellers/best_sellers_update/category_id';

    /** @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory */
    private $orderItemCollectionFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory */
    private $dateTimeFactory;

    /** @var \Zend_Db_ExprFactory */
    private $exprFactory;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Magento\Framework\App\ResourceConnectionFactory */
    private $resourceConnectionFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $scopeConfig;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * BestSellersCalculatedManagement constructor
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\App\ResourceConnectionFactory $resourceConnectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        OrderItemCollectionFactory $orderItemCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        Zend_Db_ExprFactory $exprFactory,
        AttributeRepositoryInterface $attributeRepository,
        ResourceConnectionFactory $resourceConnectionFactory,
        ScopeConfigInterface $scopeConfig,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->exprFactory = $exprFactory;
        $this->attributeRepository = $attributeRepository;
        $this->resourceConnectionFactory = $resourceConnectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Calculate best sellers
     *
     * @return int|void
     */
    public function updateBestSellers()
    {
        $productIds = $this->getProductIds();
        $this->updateAttributeValues($productIds);
        $this->assignProductsToCategory($productIds);

        return count($productIds);
    }

    /**
     * Get product ids to update best_seller_calculated
     *
     * @return int[]
     */
    private function getProductIds(): array
    {
        $dateTime = $this->dateTimeFactory->create();
        $timeStamp = $dateTime->timestamp(sprintf("-%d month", $this->getMonthsCount()));
        $date = $dateTime->gmtDate(Mysql::DATETIME_FORMAT, $timeStamp);
        $orderItemsCollection = $this->orderItemCollectionFactory->create();
        $orderItemsCollection->addFieldToFilter(OrderItemInterface::CREATED_AT, ['gteq' => $date]);
        $having = $this->exprFactory->create(
            [
                'expression' => sprintf(
                    'COUNT(%s) >= %d',
                    OrderItemInterface::PRODUCT_ID,
                    $this->getPurchaseLimit()
                )
            ]
        );
        $orderItemsCollection->getSelect()->having($having);
        $orderItemsCollection->getSelect()->group(OrderItemInterface::PRODUCT_ID);
        $productIds = $orderItemsCollection->getColumnValues(OrderItemInterface::PRODUCT_ID);

        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);

        return $productCollection->getColumnValues('entity_id');
    }

    /**
     * Update 'best_seller_calculated' attribute
     *
     * @param $productIds
     */
    public function updateAttributeValues($productIds)
    {
        $attribute = $this->getAttributeByCode(ProductBestSellerAttributesInterface::BEST_SELLER);
        if ($attribute && count($productIds)) {
            $insertData = [];
            foreach ($productIds as $productId) {
                $insertData[] = [
                    'attribute_id' => $attribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $productId,
                    'value' => 1
                ];
            }
            /** @var ResourceConnection $resourceConnection */
            $resourceConnection = $this->resourceConnectionFactory->create();
            $resourceConnection->getConnection()->insertOnDuplicate(
                $attribute->getBackendTable(),
                $insertData
            );
        }
    }

    /**
     * Assign product to category
     *
     * @param int[] $productIds
     */
    private function assignProductsToCategory($productIds)
    {
        $categoryId = $this->getCategoryId();
        if (count($productIds) && $categoryId) {
            $insertData = [];
            foreach ($productIds as $productId) {
                $insertData[] = [
                    'category_id' => $categoryId,
                    'product_id' => $productId
                ];
            }
            /** @var ResourceConnection $resourceConnection */
            $resourceConnection = $this->resourceConnectionFactory->create();
            $resourceConnection->getConnection()->insertOnDuplicate(
                $resourceConnection->getTableName('catalog_category_product'),
                $insertData
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
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $attribute;
    }

    /**
     * Get months count
     *
     * @return int
     */
    private function getMonthsCount()
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_BEST_SELLERS_CALCULATED_PURCHASE_PERIOD);
    }

    /**
     * Get purchase limit
     *
     * @return int
     */
    private function getPurchaseLimit()
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_BEST_SELLERS_CALCULATED_PURCHASE_LIMIT);
    }

    /**
     * Get category Id
     *
     * @return int
     */
    private function getCategoryId()
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_BEST_SELLERS_CALCULATED_CATEGORY_ID);
    }
}
