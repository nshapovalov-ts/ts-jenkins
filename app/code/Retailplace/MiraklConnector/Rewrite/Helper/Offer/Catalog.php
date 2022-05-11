<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

namespace Retailplace\MiraklConnector\Rewrite\Helper\Offer;

use Exception;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as ProductAttribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Model\Store;
use Mirakl\Connector\Helper\Offer\Catalog as MiraklCatalogHelper;
use Mirakl\Core\Helper\Data;
use Retailplace\AuPost\Model\AuPostAttributeUpdater;
use Retailplace\AuPost\Model\AuPostExclusiveAttributeUpdater;
use Retailplace\ChannelPricing\Model\NlnaExclusiveAttributeUpdater;
use Retailplace\ChannelPricing\Model\TierPriceUpdater;
use Retailplace\MiraklPromotion\Model\SellerSpecialsAttributeUpdater;
use Retailplace\SellerTags\Model\Updater\OpenDuringXmas;
use Zend_Db_Expr;
use Zend_Db_Select_Exception;
use Retailplace\MiraklConnector\Setup\Patch\Data\AddDifferentiatorsAttribute;
use Retailplace\MiraklConnector\Model\MarginUpdater;
use Retailplace\MiraklSellerAdditionalField\Model\ShopUpdater;

/**
 * Class Catalog
 */
class Catalog extends MiraklCatalogHelper
{
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
     * @var bool
     */
    private $isEnterprise;

    /**
     * @var TierPriceUpdater
     */
    private $tierPriceUpdater;

    /**
     * @var MarginUpdater
     */
    private $marginUpdater;

    /**
     * @var AuPostAttributeUpdater
     */
    private $auPostUpdater;

    /**
     * @var AuPostExclusiveAttributeUpdater
     */
    private $auPostExclusiveAttributeUpdater;

    /**
     * @var NlnaExclusiveAttributeUpdater
     */
    private $nlnaExclusiveAttributeUpdater;

    /**
     * @var SellerSpecialsAttributeUpdater
     */
    private $sellerSpecialsAttributeUpdater;

    /**
     * @var OpenDuringXmas
     */
    private $openDuringXmasAttributeUpdater;

    /**
     * @var ShopUpdater
     */
    private $shopUpdater;

    /**
     * Catalog constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Retailplace\ChannelPricing\Model\TierPriceUpdater $tierPriceUpdater
     * @param \Retailplace\MiraklConnector\Model\MarginUpdater $marginUpdater
     * @param \Retailplace\AuPost\Model\AuPostAttributeUpdater $auPostUpdater
     * @param \Retailplace\AuPost\Model\AuPostExclusiveAttributeUpdater $exclusiveAttributesUpdater
     * @param \Retailplace\ChannelPricing\Model\NlnaExclusiveAttributeUpdater $nlnaExclusiveAttributeUpdater
     * @param \Retailplace\MiraklPromotion\Model\SellerSpecialsAttributeUpdater $sellerSpecialsAttributeUpdater
     * @param \Retailplace\SellerTags\Model\Updater\OpenDuringXmas $openDuringXmasAttributeUpdater
     * @param ShopUpdater $shopUpdater
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        AttributeRepository $attributeRepository,
        ProductCollectionFactory $productCollectionFactory,
        TierPriceUpdater $tierPriceUpdater,
        MarginUpdater $marginUpdater,
        AuPostAttributeUpdater $auPostUpdater,
        AuPostExclusiveAttributeUpdater $exclusiveAttributesUpdater,
        NlnaExclusiveAttributeUpdater $nlnaExclusiveAttributeUpdater,
        SellerSpecialsAttributeUpdater $sellerSpecialsAttributeUpdater,
        OpenDuringXmas $openDuringXmasAttributeUpdater,
        ShopUpdater $shopUpdater
    ) {
        parent::__construct($context, $resource, $attributeRepository);
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->attributeRepository = $attributeRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->isEnterprise = Data::isEnterprise();
        $this->tierPriceUpdater = $tierPriceUpdater;
        $this->marginUpdater = $marginUpdater;
        $this->auPostUpdater = $auPostUpdater;
        $this->auPostExclusiveAttributeUpdater = $exclusiveAttributesUpdater;
        $this->nlnaExclusiveAttributeUpdater = $nlnaExclusiveAttributeUpdater;
        $this->sellerSpecialsAttributeUpdater = $sellerSpecialsAttributeUpdater;
        $this->openDuringXmasAttributeUpdater = $openDuringXmasAttributeUpdater;
        $this->shopUpdater = $shopUpdater;
    }

    /**
     * Will update mirakl_shop_ids and mirakl_offer_state_ids attributes according to mirakl_offer table data
     *
     * @param array $skus
     * @return  $this
     * @throws Zend_Db_Select_Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateAttributes(array $skus = [])
    {
        $this->updateDifferentiatorsAndBoutiqueAttribute('differentiators', 'shop_id', 'mirakl_shop', $skus);
        $this->updateAghaMemberAttribute('agha_member', 'shop_id', 'mirakl_shop', $skus);
        $this->updateFreeShippingAttribute('free_shipping', 'shop_id', 'mirakl_shop', 'free_shipping', $skus);
        $this->updateClearanceAttribute('clearance', 'shop_id', 'mirakl_shop', 'clearance', $skus);

        //on sale
        $this->updateSpecialAttribute('special_price', 'shop_id', 'mirakl_shop', 'price', $skus);
        $this->updateSpecialAttribute('special_from_date', 'shop_id', 'mirakl_shop', 'discount_start_date', $skus);
        $this->updateSpecialAttribute('special_to_date', 'shop_id', 'mirakl_shop', 'discount_end_date', $skus);

        $this->updateMinOrderAttribute('min_order_amount', 'shop_id', 'mirakl_shop', $skus);
        $this->updateShopIdAttribute('mirakl_shop_ids', 'shop_id', 'mirakl_shop', $skus);
        $this->updateShopIdAttribute('mirakl_offer_state_ids', 'state_code', 'mirakl_offer_state', $skus);
        $this->updateProductsWithNoOffers();
        $this->updatePriceIndexWithOffer($skus);
        $this->tierPriceUpdater->updateGroupPrices($skus);
        $this->marginUpdater->updateRetailMarginAttribute($skus);
        $this->auPostUpdater->update($skus);
        $this->auPostExclusiveAttributeUpdater->update($skus);
        $this->nlnaExclusiveAttributeUpdater->update($skus);
        $this->sellerSpecialsAttributeUpdater->update($skus);
        $this->openDuringXmasAttributeUpdater->run($skus);
        $this->shopUpdater->updateLeadtimeToShip($skus);
        return $this;
    }

    /**
     * Update product attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param array $skus
     * @return  $this
     */
    public function updateAghaMemberAttribute($attrCode, $offerTableField, $customTable, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "p.$entityCol",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("MAX(c.agha_seller)"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        /* Configurable product update started */
        $configurableProductSql = $this->connection->select()
            ->from(
                ['cpsl' => $this->getTableName('catalog_product_super_link')],
                ['']
            )->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'cpsl.product_id = p.entity_id',
                ''
            );

        if (!empty($entityIds)) {
            $configurableProductSql2 = new Zend_Db_Expr((clone $configurableProductSql)
                ->columns(["parent_id"])
                ->where("p.$entityCol IN (?)", $entityIds));
        }

        $configurableProductSql
            ->joinLeft(
                ['o' => $this->getTableName('mirakl_offer')],
                'o.product_sku = p.sku',
                ''
            )
            ->joinLeft(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "cpsl.parent_id",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("MAX(c.agha_seller)"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->where("p.$entityCol is not null")
            ->group('cpsl.parent_id');

        if (!empty($entityIds)) {
            $configurableProductSql->where("cpsl.parent_id in ($configurableProductSql2)");
        }

        if ($this->isEnterprise) {
            $configurableProductSql->setPart('disable_staging_preview', true);
            $configurableProductSql->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $configurableProductSql,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);
        /* Configurable product update ended */

        return $this;
    }

    /**
     * @param string $attrCode
     * @return  ProductAttribute
     * @throws  Exception
     */
    private function getAttribute($attrCode)
    {
        return $this->attributeRepository->get($attrCode);
    }

    /**
     * @param string $tableName
     * @return  string
     */
    private function getTableName($tableName)
    {
        return $this->resource->getTableName($tableName);
    }

    /**
     * Update product attribute values for 2 attribute boutique and differentiators.
     * Using direct SQL queries for better performances.
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param array $skus
     * @return $this
     * @throws Zend_Db_Select_Exception
     * @throws Exception
     */
    public function updateDifferentiatorsAndBoutiqueAttribute(string $attrCode, string $offerTableField, string $customTable, array $skus = []): Catalog
    {
        $attribute = $this->getAttribute($attrCode);
        $options = $attribute->getOptions();
        $boutiqueAttribute = $this->getAttribute('boutique');
        $differentiatorMapping = AddDifferentiatorsAttribute::DIFFERENTIATORS_MAPPING;
        $differentiatorMappingWithOptionIds = [];
        foreach ($options as $option) {
            if (isset($differentiatorMapping[$option->getLabel()])) {
                $differentiatorMappingWithOptionIds[$differentiatorMapping[$option->getLabel()]] = $option->getValue();
            }
        }
        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                'differentiators'
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                'entity_id'
            )
            ->where('o.active = ?', 'true')
            ->group('o.product_sku');
        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }
        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }
        $simplerMiraklOffers = $this->connection->fetchAll($select);

        $attributeData = [];
        $boutiqueAttributeData = [];
        foreach ($simplerMiraklOffers as $miraklOffer) {
            $differentiators = $miraklOffer['differentiators'] ? explode(",", $miraklOffer['differentiators']) : [];
            $selectedOptionIds = [];
            foreach ($differentiators as $differentiator) {
                if (isset($differentiatorMappingWithOptionIds[$differentiator])) {
                    $selectedOptionIds[] = $differentiatorMappingWithOptionIds[$differentiator];
                }
            }
            $attributeData[] = [
                'attribute_id' => $attribute->getAttributeId(),
                'store_id'     => Store::DEFAULT_STORE_ID,
                $entityCol     => $miraklOffer['entity_id'],
                'value'        => implode(",", $selectedOptionIds),
            ];

            $boutiqueAttributeData[] = [
                'attribute_id' => $boutiqueAttribute->getAttributeId(),
                'store_id'     => Store::DEFAULT_STORE_ID,
                $entityCol     => $miraklOffer['entity_id'],
                'value'        => in_array('Boutique', $differentiators),
            ];
        }

        /* Configurable product addition started */
        $configurableProductSql = $this->connection->select()
            ->from(
                ['cpsl' => $this->getTableName('catalog_product_super_link')],
                [""]
            )->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'cpsl.product_id = p.entity_id',
                ''
            );

        if (!empty($entityIds)) {
            $configurableProductSql2 = new Zend_Db_Expr((clone $configurableProductSql)
                ->columns(['cpsl.parent_id'])
                ->where("p.$entityCol IN (?)", $entityIds));
        }

        $configurableProductSql
            ->joinLeft(
                ['o' => $this->getTableName('mirakl_offer')],
                'o.product_sku = p.sku',
                ''
            )
            ->joinLeft(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->columns([
                $entityCol        => "cpsl.parent_id",
                'differentiators' => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT(`c`.`differentiators`))")
            ])
            ->where('o.active = ?', 'true')
            ->where("p.$entityCol is not null")
            ->group('cpsl.parent_id');

        if (!empty($entityIds)) {
            $configurableProductSql->where("cpsl.parent_id in ($configurableProductSql2)");
        }

        if ($this->isEnterprise) {
            $configurableProductSql->setPart('disable_staging_preview', true);
            $configurableProductSql->group('p.row_id');
        }
        $configurableMiraklOffers = $this->connection->fetchAll($configurableProductSql);
        foreach ($configurableMiraklOffers as $configurableMiraklOffer) {
            $differentiators = $configurableMiraklOffer['differentiators'] ? explode(",", $configurableMiraklOffer['differentiators']) : [];
            $selectedOptionIds = [];
            foreach ($differentiators as $differentiator) {
                if (isset($differentiatorMappingWithOptionIds[$differentiator])) {
                    $selectedOptionIds[] = $differentiatorMappingWithOptionIds[$differentiator];
                }
            }
            $attributeData[] = [
                'attribute_id' => $attribute->getAttributeId(),
                'store_id'     => Store::DEFAULT_STORE_ID,
                $entityCol     => $configurableMiraklOffer['entity_id'],
                'value'        => implode(",", $selectedOptionIds),
            ];
            $boutiqueAttributeData[] = [
                'attribute_id' => $boutiqueAttribute->getAttributeId(),
                'store_id'     => Store::DEFAULT_STORE_ID,
                $entityCol     => $configurableMiraklOffer['entity_id'],
                'value'        => in_array('Boutique', $differentiators),
            ];
        }
        /* Configurable product addition ended */

        if ($attributeData) {
            $this->connection->insertOnDuplicate(
                $attribute->getBackendTable(),
                $attributeData,
                ['attribute_id', 'store_id', $entityCol, 'value']
            );
        }

        if ($boutiqueAttributeData) {
            $this->connection->insertOnDuplicate(
                $boutiqueAttribute->getBackendTable(),
                $boutiqueAttributeData,
                ['attribute_id', 'store_id', $entityCol, 'value']
            );
        }
        return $this;
    }

    /**
     * Update product attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param array $skus
     * @return  $this
     * @throws Zend_Db_Select_Exception
     */
    public function updateMinOrderAttribute($attrCode, $offerTableField, $customTable, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "p.$entityCol",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("(MAX(`c`.`min-order-amount`))"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        /* Configurable product update started */
        $configurableProductSql = $this->connection->select()
            ->from(
                ['cpsl' => $this->getTableName('catalog_product_super_link')],
                [""]
            )->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'cpsl.product_id = p.entity_id',
                ''
            );

        if (!empty($entityIds)) {
            $configurableProductSql2 = new Zend_Db_Expr((clone $configurableProductSql)
                ->columns(['cpsl.parent_id'])
                ->where("p.$entityCol IN (?)", $entityIds));
        }

        $configurableProductSql
            ->joinLeft(
                ['o' => $this->getTableName('mirakl_offer')],
                'o.product_sku = p.sku',
                ''
            )
            ->joinLeft(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "cpsl.parent_id",
                'value'        => new Zend_Db_Expr("(MAX(`c`.`min-order-amount`))")
            ])
            ->where('o.active = ?', 'true')
            ->where("p.$entityCol is not null")
            ->order('c.min-order-amount DESC')
            ->group('cpsl.parent_id');

        if (!empty($entityIds)) {
            $configurableProductSql->where("cpsl.parent_id in ($configurableProductSql2)");
        }

        if ($this->isEnterprise) {
            $configurableProductSql->setPart('disable_staging_preview', true);
            $configurableProductSql->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $configurableProductSql,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);
        /* Configurable product update ended */
        return $this;
    }

    /**
     * Update product attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param array $skus
     * @return  $this
     */
    public function updateShopIdAttribute($attrCode, $offerTableField, $customTable, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }

        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "p.$entityCol",
                'value'        => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT c.eav_option_id SEPARATOR ',')")
            ])
            ->where('o.active = ?', 'true')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        /* Configurable product update started */
        $configurableProductSql = $this->connection->select()
            ->from(
                ['cpsl' => $this->getTableName('catalog_product_super_link')],
                [""]
            )->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'cpsl.product_id = p.entity_id',
                ''
            );

        if (!empty($entityIds)) {
            $configurableProductSql2 = new Zend_Db_Expr((clone $configurableProductSql)
                ->columns(["parent_id"])
                ->where("p.$entityCol IN (?)", $entityIds));
        }

        $configurableProductSql->joinLeft(
            ['o' => $this->getTableName('mirakl_offer')],
            'o.product_sku = p.sku',
            ''
        )->joinLeft(
            ['c' => $this->getTableName($customTable)],
            "o.$offerTableField = c.id",
            ''
        )->columns([
            'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
            'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
            $entityCol     => "cpsl.parent_id",
            'value'        => new Zend_Db_Expr("GROUP_CONCAT(DISTINCT c.eav_option_id SEPARATOR ',')")
        ])
            ->where('o.active = ?', 'true')
            ->where("p.$entityCol is not null")
            ->group('cpsl.parent_id');

        if (!empty($entityIds)) {
            $configurableProductSql->where("cpsl.parent_id in ($configurableProductSql2)");
        }

        if ($this->isEnterprise) {
            $configurableProductSql->setPart('disable_staging_preview', true);
            $configurableProductSql->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $configurableProductSql,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);
        /* Configurable product update ended */

        return $this;
    }

    /**
     * Update product attribute values for products with no offers
     * @return $this
     */
    public function updateProductsWithNoOffers()
    {
        $attrCodes = [
            'agha_member',
            'min_order_amount',
            'mirakl_shop_ids',
            'mirakl_offer_state_ids',
            'free_shipping',
            'clearance',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date'
        ];

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        foreach ($attrCodes as $attrCode) {
            /** @var ProductCollection $productCollection */
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter($attrCode, ['notnull' => true]);
            $productCollection->addAttributeToFilter('type_id', 'simple');

            $attribute = $this->getAttribute($attrCode);

            $select = $productCollection->getSelect()
                ->joinLeft(
                    ['o' => $this->getTableName('mirakl_offer')],
                    'o.entity_id = e.entity_id AND o.active = "true"',
                    ''
                )
                ->reset(Select::COLUMNS)
                ->columns([
                    'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                    'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                    $entityCol     => "e.$entityCol",
                    'value'        => new Zend_Db_Expr('null')
                ])
                ->where('o.offer_id IS NULL');

            if ($this->isEnterprise) {
                $select->setPart('disable_staging_preview', true);
                $select->group('e.row_id');
            }

            $sql = $this->connection->insertFromSelect(
                $select,
                $attribute->getBackendTable(),
                ['attribute_id', 'store_id', $entityCol, 'value'],
                AdapterInterface::INSERT_ON_DUPLICATE
            );
            $this->connection->query($sql);

            /** @var ProductCollection $productCollection */
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter($attrCode, ['notnull' => true]);

            $configurableProductSql = $productCollection->getSelect()
                ->join(
                    ['cpsl' => 'catalog_product_super_link'],
                    '`cpsl`.`parent_id` = `e`.`entity_id`',
                    ''
                )
                ->joinLeft(
                    ['o' => $this->getTableName('mirakl_offer')],
                    'o.entity_id = cpsl.product_id AND o.active = "true"',
                    ''
                )
                ->reset(Select::COLUMNS)
                ->columns([
                    'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                    'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                    $entityCol     => "e.$entityCol",
                    'value'        => new Zend_Db_Expr('null')
                ])
                ->having(new Zend_Db_Expr("MAX(`o`.`offer_id`) IS NULL"))
                ->group('cpsl.parent_id');

            if ($this->isEnterprise) {
                $configurableProductSql->setPart('disable_staging_preview', true);
                $configurableProductSql->group('e.row_id');
            }
            $sql = $this->connection->insertFromSelect(
                $configurableProductSql,
                $attribute->getBackendTable(),
                ['attribute_id', 'store_id', $entityCol, 'value'],
                AdapterInterface::INSERT_ON_DUPLICATE
            );
            $this->connection->query($sql);
        }
        return $this;
    }

    /**
     * Update Free Shipping attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param string $customTableField
     * @param array $skus
     * @return  $this
     * @throws Zend_Db_Select_Exception
     */
    public function updateFreeShippingAttribute($attrCode, $offerTableField, $customTable, $customTableField, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "p.$entityCol",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("MAX(c." . $customTableField . ")"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        /* Configurable product update started */
        $configurableProductSql = $this->connection->select()
            ->from(
                ['cpsl' => $this->getTableName('catalog_product_super_link')],
                ['']
            )->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'cpsl.product_id = p.entity_id',
                ''
            );

        if (!empty($entityIds)) {
            $configurableProductSql2 = new Zend_Db_Expr((clone $configurableProductSql)
                ->columns(["parent_id"])
                ->where("p.$entityCol IN (?)", $entityIds));
        }

        $configurableProductSql
            ->joinLeft(
                ['o' => $this->getTableName('mirakl_offer')],
                'o.product_sku = p.sku',
                ''
            )
            ->joinLeft(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "cpsl.parent_id",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("MAX(c." . $customTableField . ")"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->where("p.$entityCol is not null")
            ->group('cpsl.parent_id');

        if (!empty($entityIds)) {
            $configurableProductSql->where("cpsl.parent_id in ($configurableProductSql2)");
        }

        if ($this->isEnterprise) {
            $configurableProductSql->setPart('disable_staging_preview', true);
            $configurableProductSql->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $configurableProductSql,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);
        /* Configurable product update ended */

        return $this;
    }

    /**
     * Update Clearance attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param string $offerTableName
     * @param array $skus
     * @return $this
     * @throws Zend_Db_Select_Exception
     */
    public function updateClearanceAttribute($attrCode, $offerTableField, $customTable, $offerTableName, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "p.$entityCol",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("MAX(o." . $offerTableName . ")"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->where('o.segment = ?', '')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        /* Configurable product update started */
        $configurableProductSql = $this->connection->select()
            ->from(
                ['cpsl' => $this->getTableName('catalog_product_super_link')],
                ['']
            )->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'cpsl.product_id = p.entity_id',
                ''
            );

        if (!empty($entityIds)) {
            $configurableProductSql2 = new Zend_Db_Expr((clone $configurableProductSql)
                ->columns(["parent_id"])
                ->where("p.$entityCol IN (?)", $entityIds));
        }

        $configurableProductSql
            ->joinLeft(
                ['o' => $this->getTableName('mirakl_offer')],
                'o.product_sku = p.sku',
                ''
            )
            ->joinLeft(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "cpsl.parent_id",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("MAX(o." . $offerTableName . ")"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->where('o.segment = ?', '')
            ->where("p.$entityCol is not null")
            ->group('cpsl.parent_id');

        if (!empty($entityIds)) {
            $configurableProductSql->where("cpsl.parent_id in ($configurableProductSql2)");
        }

        if ($this->isEnterprise) {
            $configurableProductSql->setPart('disable_staging_preview', true);
            $configurableProductSql->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $configurableProductSql,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);
        /* Configurable product update ended */

        return $this;
    }



    /**
     * Update On Sale attribute values.
     * Using direct SQL queries for better performances.
     *
     * @param string $attrCode
     * @param string $offerTableField
     * @param string $customTable
     * @param $offerTableName
     * @param array $skus
     * @return  $this
     * @throws Zend_Db_Select_Exception
     */
    public function updateSpecialAttribute($attrCode, $offerTableField, $customTable, $offerTableName, array $skus = [])
    {
        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName('mirakl_offer')], '')
            ->join(
                ['c' => $this->getTableName($customTable)],
                "o.$offerTableField = c.id",
                ''
            )
            ->join(
                ['p' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = p.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "p.$entityCol"
            ])
            ->where('o.active = ?', 'true')
            ->where('o.segment = ?', '')
            ->where('o.price < o.origin_price')
            ->group('o.product_sku');

        if (in_array($attrCode, ['special_from_date', 'special_to_date'])) {
            $select->columns([
                'value' => new Zend_Db_Expr(
                    "IF(MIN(o." . $offerTableName . ") = '0000-00-00 00:00:00', NULL, MIN(o." . $offerTableName . "))"
                )
            ]);
        } else {
            $select->columns([
                'value' => new Zend_Db_Expr("MIN(o." . $offerTableName . ")")
            ]);
        }

        if (!empty($entityIds)) {
            $select->where("p.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('p.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);

        return $this;
    }

    /**
     * Update product price attribute values for products with offers
     * @param array $skus
     * @return $this
     * @throws \Zend_Db_Select_Exception
     */
    public function updatePriceIndexWithOffer(array $skus)
    {
        $attrCode = "price";
        $customTable = "mirakl_offer";

        $attribute = $this->getAttribute($attrCode);

        $entityCol = $this->isEnterprise ? 'row_id' : 'entity_id';

        $entityIds = []; // Will be used to filter entity ids to update

        if (!empty($skus)) {
            $select = $this->connection->select()
                ->from($this->getTableName('catalog_product_entity'), [$entityCol])
                ->where('sku IN (?)', $skus);
            $entityIds = $this->connection->fetchCol($select);

            if (empty($entityIds)) {
                return $this; // Do not do anything if we cannot find any products with given skus
            }
        }

        // Reset all values of this attribute
        $params = ['attribute_id = ?' => $attribute->getAttributeId()];
        if (!empty($entityIds)) {
            $params["$entityCol IN (?)"] = $entityIds;
        }
        $this->connection->update($attribute->getBackendTable(), ['value' => null], $params);

        // Update values of this attribute
        $select = $this->connection->select()
            ->from(['o' => $this->getTableName($customTable)], '')
            ->join(
                ['e' => $this->getTableName('catalog_product_entity')],
                'o.product_sku = e.sku',
                ''
            )
            ->columns([
                'attribute_id' => new Zend_Db_Expr($attribute->getAttributeId()),
                'store_id'     => new Zend_Db_Expr(Store::DEFAULT_STORE_ID),
                $entityCol     => "e.$entityCol",
                'value'        => $this->connection->getIfNullSql(new Zend_Db_Expr("(MIN(`o`.`origin_price`))"), 0)
            ])
            ->where('o.active = ?', 'true')
            ->where('o.segment = ?', '')
            ->group('o.product_sku');

        if (!empty($entityIds)) {
            $select->where("e.$entityCol IN (?)", $entityIds);
        }

        // Disable staging preview in EE and handle multiple row ids for unique entity_id
        if ($this->isEnterprise) {
            $select->setPart('disable_staging_preview', true);
            $select->group('e.row_id');
        }

        $sql = $this->connection->insertFromSelect(
            $select,
            $attribute->getBackendTable(),
            ['attribute_id', 'store_id', $entityCol, 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $this->connection->query($sql);
        return $this;
    }
}
