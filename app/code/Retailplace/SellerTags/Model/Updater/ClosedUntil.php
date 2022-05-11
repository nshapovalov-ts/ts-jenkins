<?php

/**
 * Retailplace_SellerTags
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerTags\Model\Updater;

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
use Retailplace\AttributesUpdater\Model\Updater\AbstractUpdater;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Zend_Db_ExprFactory;

/**
 * Class ClosedUntil
 */
class ClosedUntil extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = SellerTagsAttributes::PRODUCT_CLOSED_TO;

    /** @var null */
    protected $clearedValue = null;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /** @var array */
    private $affectedProductData = [];

    /** @var array */
    private $shopsData = [];

    /**
     * ClosedUntil Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory $shopCollectionFactory
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
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

        $this->shopCollectionFactory = $shopCollectionFactory;
        $this->dateTime = $dateTime;
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
        $closedToAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $closedToData = [];
        $shopsData = $this->getShopsData();

        /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
        foreach ($this->getAffectedOffersList() as $offer) {
            if (!empty($productData[$offer->getProductSku()]) && isset($shopsData[$offer->getShopId()])) {
                $productId = $productData[$offer->getProductSku()];
                $closedToData[$productId] = $shopsData[$offer->getShopId()];
            }
        }

        $closedToInsertData = [];
        foreach ($ids as $productId) {
            if (!empty($closedToData[$productId])) {
                $closedToInsertData[] = [
                    'attribute_id' => $closedToAttribute->getAttributeId(),
                    'store_id' => Store::DEFAULT_STORE_ID,
                    'entity_id' => $productId,
                    'value' => $closedToData[$productId]
                ];
            }
        }

        $this->insertData($closedToInsertData, $closedToAttribute->getBackendTable());
        $this->addDataToParents($closedToInsertData);

        return $closedToInsertData;
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

    /**
     * Get all Shop IDs with necessary attributes values
     *
     * @return int[]
     */
    private function getShopIds(): array
    {
        $now = $this->dateTime->gmtDate();
        /** @var \Mirakl\Core\Model\ResourceModel\Shop\Collection $shopCollection */
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection
            ->addFieldToFilter(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_FROM, ['lteq' => $now])
            ->addFieldToFilter(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_TO, ['gteq' => $now]);

        foreach ($shopCollection->getItems() as $shop) {
            $this->shopsData[$shop->getId()] = $shop->getData(SellerTagsAttributes::SHOP_HOLIDAY_CLOSED_TO);
        }

        return $shopCollection->getAllIds();
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
     * @param array $insertData
     */
    private function addDataToParents(array $insertData)
    {
        $closedToAttribute = $this->getAttributeByCode($this->getAttributeCode());

        $parentsData = $this->getConfigurableProductsByChildren($this->getAffectedProductIds(), true);

        $closedTo = [];
        foreach ($parentsData as $row) {
            $simpleId = $row['product_id'];
            $parentId = $row['parent_id'];

            foreach ($insertData as $dataRow) {
                if ($dataRow['entity_id'] == $simpleId) {
                    $closedTo[$parentId] = $dataRow['value'];
                }
            }
        }

        $parentInsertData = [];
        foreach ($closedTo as $productId => $value) {
            $parentInsertData[] = [
                'attribute_id' => $closedToAttribute->getAttributeId(),
                'store_id'     => Store::DEFAULT_STORE_ID,
                'entity_id'    => $productId,
                'value'        => $value
            ];
        }

        if (count($parentInsertData)) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $closedToAttribute->getBackendTable(),
                $parentInsertData
            );
        }
    }
}
