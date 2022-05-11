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
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Retailplace\MiraklConnector\Setup\Patch\Data\AddBoutiqueAttribute;
use Retailplace\MiraklConnector\Setup\Patch\Data\AddDifferentiatorsAttribute;
use Zend_Db_ExprFactory;

/**
 * Class Differentiators
 */
class Differentiators extends AbstractUpdater implements UpdaterInterface
{
    /** @var string */
    protected $attributeCode = AddDifferentiatorsAttribute::DIFFERENTIATORS;

    /** @var string|int|null */
    protected $clearedValue = null;

    /** @var \Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory */
    private $shopCollectionFactory;

    /** @var array */
    private $affectedProductData = [];

    /** @var array */
    private $differentiatorMapping = [];

    /**
     * Differentiators constructor
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
     * Update Attribute Values for Products
     *
     * @param string[] $skus
     */
    public function run(array $skus = [])
    {
        $skusFromOffers = $this->getOfferSkus($skus);
        $productIdsFromOffers = $this->getProductIds($skusFromOffers);
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers);
        $this->clearProductsFromAttribute($skus, $productIdsFromOffers, AddBoutiqueAttribute::BOUTIQUE);
        $insertData = $this->addAttributeToProducts($productIdsFromOffers);
        $this->addDataToParents($productIdsFromOffers);
    }

    /**
     * Update Attributes
     *
     * @param int[] $ids
     * @return array
     */
    protected function addAttributeToProducts(array $ids): array
    {
        $insertDataDifferentiators = [];
        $insertDataBoutique = [];

        $differentiatorAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $boutiqueAttribute = $this->getAttributeByCode(AddBoutiqueAttribute::BOUTIQUE);

        $differentiatorsMapping = $this->getDifferentiatorMapping();
        foreach ($ids as $productId) {
            $productData = $this->getAffectedProductData();
            if (isset($productData[$productId])) {
                $differentiators = explode(',', $productData[$productId]);
                $optionIds = [];
                foreach ($differentiators as $differentiator) {
                    if (!empty($differentiatorsMapping[$differentiator])) {
                        $optionIds[] = $differentiatorsMapping[$differentiator];
                    }
                }

                $insertDataDifferentiators[] = [
                    'attribute_id' => $differentiatorAttribute->getAttributeId(),
                    'store_id'     => Store::DEFAULT_STORE_ID,
                    'entity_id'    => $productId,
                    'value'        => implode(',', array_unique($optionIds))
                ];

                $insertDataBoutique[] = [
                    'attribute_id' => $boutiqueAttribute->getAttributeId(),
                    'store_id'     => Store::DEFAULT_STORE_ID,
                    'entity_id'    => $productId,
                    'value'        => in_array('Boutique', $differentiators) ? $this->getActiveValue() : $this->getClearedValue()
                ];
            }
        }

        $this->insertData($insertDataDifferentiators, $differentiatorAttribute->getBackendTable());
        $this->insertData($insertDataBoutique, $boutiqueAttribute->getBackendTable());

        return [
            'differentiators' => $insertDataDifferentiators,
            'boutique' => $insertDataBoutique
        ];
    }

    /**
     * Extend Select to get Product Ids
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function extendProductIdsSelect(Select $select): Select
    {
        $select
            ->joinInner(
                [$this->resourceConnection->getTableName('mirakl_offer')],
                'mirakl_offer.product_sku = catalog_product_entity.sku',
                ''
            )
            ->joinInner(
                [$this->resourceConnection->getTableName('mirakl_shop')],
                'mirakl_shop.id = mirakl_offer.shop_id',
                'differentiators'
            )
            ->group('catalog_product_entity.sku');

        $this->affectedProductData = $this->resourceConnection->getConnection()->fetchAll($select);

        return $select;
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
     * Get Products with Differentiators
     *
     * @return array
     */
    private function getAffectedProductData(): array
    {
        $productData = [];
        foreach ($this->affectedProductData as $row) {
            $productData[$row['entity_id']] = $row['differentiators'];
        }

        return $productData;
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
        $shopCollection->addFieldToFilter('differentiators', ['neq' => 'NULL']);

        return $shopCollection->getAllIds();
    }

    /**
     * Get Differentiators Mapping with Attribute Option Values
     *
     * @return array
     */
    private function getDifferentiatorMapping(): array
    {
        if (!$this->differentiatorMapping) {
            $differentiatorMapping = AddDifferentiatorsAttribute::DIFFERENTIATORS_MAPPING;
            $differentiatorAttribute = $this->getAttributeByCode($this->getAttributeCode());
            $this->differentiatorMapping = [];
            foreach ($differentiatorAttribute->getOptions() as $option) {
                if (isset($differentiatorMapping[$option->getLabel()])) {
                    $this->differentiatorMapping[$differentiatorMapping[$option->getLabel()]] = $option->getValue();
                }
            }
        }

        return $this->differentiatorMapping;
    }

    /**
     * Add merged data to Configurable Products
     *
     * @param array $productIds
     */
    private function addDataToParents(array $productIds)
    {
        $differentiatorAttribute = $this->getAttributeByCode($this->getAttributeCode());
        $boutiqueAttribute = $this->getAttributeByCode(AddBoutiqueAttribute::BOUTIQUE);

        $allChildren = $this->getChildrenIdsForConfigurableProducts($productIds, true);
        $productRelationsData = $this->prepareConfigurableRelationsData($allChildren);

        $insertDataDifferentiators = $this->collectParentDifferentiatorsData($productRelationsData);
        $insertDataBoutique = $this->collectParentBoutiqueData($productRelationsData);
        $this->insertData($insertDataDifferentiators, $differentiatorAttribute->getBackendTable());
        $this->insertData($insertDataBoutique, $boutiqueAttribute->getBackendTable());

    }

    /**
     * Prepare Data for Configurable Products depends on Children
     *
     * @param array $productRelationsData
     * @return array
     */
    private function collectParentDifferentiatorsData(array $productRelationsData): array
    {
        $insertDataDifferentiators = [];
        $differentiatorAttribute = $this->getAttributeByCode($this->getAttributeCode());

        $childrenIds = array_keys($productRelationsData);
        if (count($productRelationsData)) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection
                ->select()
                ->from(['a' => $differentiatorAttribute->getBackendTable()])
                ->where('a.attribute_id = ?', $differentiatorAttribute->getAttributeId())
                ->where('a.entity_id IN (?)', $childrenIds)
                ->where('a.store_id = ?', Store::DEFAULT_STORE_ID);
            $differentiatorData = $connection->fetchAll($select);

            $parentDifferentiators = [];
            if (count($differentiatorData)) {
                foreach ($differentiatorData as $row) {
                    if (!empty($productRelationsData[$row['entity_id']])) {
                        if (!empty($parentDifferentiators[$productRelationsData[$row['entity_id']]])) {
                            $parentDifferentiators[$productRelationsData[$row['entity_id']]] .= ',' . $row['value'];
                        } else {
                            $parentDifferentiators[$productRelationsData[$row['entity_id']]] = $row['value'];
                        }
                    }
                }

                foreach ($parentDifferentiators as $parentId => $data) {
                    $data = array_filter(explode(',', $data));
                    if (count($data)) {
                        $insertDataDifferentiators[] = [
                            'attribute_id' => $differentiatorAttribute->getAttributeId(),
                            'store_id'     => Store::DEFAULT_STORE_ID,
                            'entity_id'    => $parentId,
                            'value'        => implode(',', array_unique($data))
                        ];
                    }
                }
            }

        }

        return $insertDataDifferentiators;
    }

    /**
     * Prepare Data for Configurable Products depends on Children
     *
     * @param array $productRelationsData
     * @return array
     */
    private function collectParentBoutiqueData(array $productRelationsData): array
    {
        $insertDataBoutique = [];
        $boutiqueAttribute = $this->getAttributeByCode(AddBoutiqueAttribute::BOUTIQUE);
        $childrenIds = array_keys($productRelationsData);
        if (count($productRelationsData)) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection
                ->select()
                ->from(['a' => $boutiqueAttribute->getBackendTable()])
                ->where('a.attribute_id = ?', $boutiqueAttribute->getAttributeId())
                ->where('a.entity_id IN (?)', $childrenIds)
                ->where('a.store_id = ?', Store::DEFAULT_STORE_ID);
            $boutiqueData = $connection->fetchAll($select);
            $parentBoutique = [];
            if (count($boutiqueData)) {
                foreach ($boutiqueData as $row) {
                    if ($row['value'] && !empty($productRelationsData[$row['entity_id']])) {
                        $parentBoutique[$productRelationsData[$row['entity_id']]] = 1;
                    }
                }

                foreach ($parentBoutique as $parentId => $data) {
                    $insertDataBoutique[] = [
                        'attribute_id' => $boutiqueAttribute->getAttributeId(),
                        'store_id' => Store::DEFAULT_STORE_ID,
                        'entity_id' => $parentId,
                        'value' => $data
                    ];
                }
            }
        }

        return $insertDataBoutique;
    }
}
