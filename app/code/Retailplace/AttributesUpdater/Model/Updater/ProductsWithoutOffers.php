<?php

/**
 * Retailplace_AttributesUpdater
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AttributesUpdater\Model\Updater;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Zend_Db_ExprFactory;

/**
 * Class ProductsWithoutOffers
 */
class ProductsWithoutOffers extends AbstractUpdater implements UpdaterInterface
{
    /** @var null */
    protected $clearedValue = null;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /**
     * ProductsWithoutOffers Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
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
        ProductCollectionFactory $productCollectionFactory,
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

        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Run Updater
     *
     * @param array $skus
     */
    public function run(array $skus = [])
    {
        foreach ($this->getAttributesList() as $attributeCode) {
            $attribute = $this->getAttributeByCode($attributeCode);
            $this->updateSimpleProducts($attribute);
            $this->updateConfigurableProducts($attribute);
        }
    }

    /**
     * Clear Attribute for Simple Products
     *
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     */
    private function updateSimpleProducts(AttributeInterface $attribute)
    {
        $connection = $this->resourceConnection->getConnection();

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter($attribute->getAttributeCode(), ['notnull' => true]);
        $productCollection->addAttributeToFilter(ProductInterface::TYPE_ID, Type::TYPE_SIMPLE);
        $productCollection->getSelect()
            ->joinLeft(
                ['o' => $connection->getTableName('mirakl_offer')],
                $this->getDbExpression('o.product_sku = e.sku AND o.active = "true"'),
                ''
            )
            ->reset(Select::COLUMNS)
            ->columns(['entity_id' => 'e.entity_id'])
            ->where('o.offer_id IS NULL');

        $simpleIds = $productCollection->getAllIds();
        if (count($simpleIds)) {
            $this->resourceConnection->getConnection()->update(
                $attribute->getBackendTable(),
                ['value' => $this->getClearedValue()],
                [
                    'attribute_id = ?' => $attribute->getAttributeId(),
                    'entity_id IN (?)' => $simpleIds
                ]
            );
        }
    }

    /**
     * Clear Attribute for Configurable Products
     *
     * @param \Magento\Eav\Api\Data\AttributeInterface $attribute
     */
    private function updateConfigurableProducts(AttributeInterface $attribute)
    {
        $connection = $this->resourceConnection->getConnection();

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter($attribute->getAttributeCode(), ['notnull' => true]);
        $productCollection->getSelect()
            ->join(
                ['cpsl' => 'catalog_product_super_link'],
                $this->getDbExpression('`cpsl`.`parent_id` = `e`.`entity_id`'),
                ''
            )
            ->joinLeft(
                ['o' => $connection->getTableName('mirakl_offer')],
                $this->getDbExpression('o.entity_id = cpsl.product_id AND o.active = "true"'),
                ''
            )
            ->reset(Select::COLUMNS)
            ->columns(['entity_id' => 'e.entity_id'])
            ->having($this->getDbExpression('MAX(`o`.`offer_id`) IS NULL'))
            ->group('cpsl.parent_id');

        $configurableProductIds = [];
        foreach ($productCollection->getItems() as $product) {
            $configurableProductIds[] = $product->getEntityId();
        }

        if (count($configurableProductIds)) {
            $this->resourceConnection->getConnection()->update(
                $attribute->getBackendTable(),
                ['value' => $this->getClearedValue()],
                [
                    'attribute_id = ?' => $attribute->getAttributeId(),
                    'entity_id IN (?)' => $configurableProductIds
                ]
            );
        }
    }

    /**
     * Get list of Attribute Codes to clear
     *
     * @return string[]
     */
    private function getAttributesList(): array
    {
        return [
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
    }
}
