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
 * Class Price
 */
class Price extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = 'price';

    /** @var null */
    protected $clearedValue = null;

    /** @var array */
    private $affectedProductData = [];

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder->addFilter(OfferInterface::SEGMENT, '');

        return $searchCriteriaBuilder;
    }

    /**
     * Get Offers SKU List
     *
     * @param array $skus
     * @return string[]
     */
    protected function getOfferSkus(array $skus): array
    {
        return parent::getOfferSkusAlt($skus);
    }

    /**
     * Extend Offers Select
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function extendOffersSelect(Select $select): Select
    {
        $select->where('segment = ?', '');

        return $select;
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
        $priceAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $prices = [];

        /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
        foreach ($this->getAffectedOffersList() as $offer) {
            if (!is_array($offer)) {
                $offer = $offer->getData();
            }
            if (!empty($productData[$offer[OfferInterface::PRODUCT_SKU]])) {
                $productId = $productData[$offer[OfferInterface::PRODUCT_SKU]];
                $prices[$productId][] = $offer[OfferInterface::PRICE];
            }
        }

        $pricesInsertData = [];
        foreach ($ids as $productId) {
            if (!empty($prices[$productId])) {
                $pricesInsertData[] = [
                    'attribute_id' => $priceAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $productId,
                    'value' => min($prices[$productId])
                ];
            }
        }

        $this->insertData($pricesInsertData, $priceAttribute->getBackendTable());

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
}
