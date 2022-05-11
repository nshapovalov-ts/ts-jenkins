<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
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
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Magento\Framework\Setup\Declaration\Schema\Db\MySQL\Definition\Columns\Timestamp;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Zend_Db_ExprFactory;

/**
 * Class SpecialPrice
 */
class SpecialPrice extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = 'special_price';

    /** @var null */
    protected $clearedValue = null;

    /** @var array */
    private $affectedProductData = [];

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /**
     * SpecialPrice Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
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
        DateTime $dateTime,
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

        $this->dateTime = $dateTime;
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
            ->addFilter(OfferInterface::SEGMENT, '')
            ->addFilter(
                OfferInterface::PRICE,
                $this->getDbExpression(sprintf('`%s`', OfferInterface::ORIGIN_PRICE)),
                'lt'
            );

        return $searchCriteriaBuilder;
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
        $specialPriceAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $prices = [];

        /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
        foreach ($this->getAffectedOffersList() as $offer) {
            if (!empty($productData[$offer->getProductSku()]) && $this->isOfferDiscountActive($offer)) {
                $productId = $productData[$offer->getProductSku()];
                $prices[$productId][] = $offer->getPrice();
            }
        }

        $pricesInsertData = [];
        foreach ($ids as $productId) {
            if (!empty($prices[$productId])) {
                $pricesInsertData[] = [
                    'attribute_id' => $specialPriceAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $productId,
                    'value' => min($prices[$productId])
                ];
            }
        }

        $this->insertData($pricesInsertData, $specialPriceAttribute->getBackendTable());

        return $pricesInsertData;
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
     * Disable extending with Configurable Products
     *
     * @param int[] $ids
     * @param bool $fullData
     * @return int[]
     */
    protected function getConfigurableProductsByChildren(array $ids, bool $fullData = false): array
    {
        return [];
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
}
