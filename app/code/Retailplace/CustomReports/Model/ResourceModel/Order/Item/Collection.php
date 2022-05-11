<?php
/**
 * Retailplace_CustomReports
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@vdcstore.com>
 */
namespace Retailplace\CustomReports\Model\ResourceModel\Order\Item;

use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Catalog\Model\ResourceModel\Config;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Class Collection
 * Collection for displaying grid of sales documents
 */
class Collection extends SearchResult
{
    /**
     * @var Config
     */
    private $catalogConfig;
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;
    /**
     * @var int
     */
    private $customerEntityTypeId;

    /**
     * Collection constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param Config $catalogConfig
     * @param Customer $customerModel
     * @param null $resourceModel
     * @param null $identifierName
     * @param null $connectionName
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        Config $catalogConfig,
        \Magento\Eav\Model\Config $eavConfig,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->eavConfig = $eavConfig;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel, $identifierName, $connectionName);

    }

    /**
     * @param $attributeCode
     * @param int|null $entityTypeId
     * @return string
     */
    public function getAttributeIdByCode($attributeCode, int $entityTypeId = null): string
    {
        if ($entityTypeId == null) {
            $entityTypeId = $this->catalogConfig->getEntityTypeId();
        }
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('eav_attribute'), ['attribute_id'])
            ->where('attribute_code = ?', $attributeCode)
            ->where('entity_type_id = ?', $entityTypeId);
        return $connection->fetchOne($select);
    }

    /**
     * @return int
     * @throws LocalizedException
     */
    public function getCustomerEntityTypeId(): int
    {
        if ($this->customerEntityTypeId == null) {
            $this->customerEntityTypeId = (int) $this->eavConfig->getEntityType(\Magento\Customer\Model\Customer::ENTITY)
                ->getId();
        }
        return $this->customerEntityTypeId;
    }

    /**
     * @return $this|Collection
     * @throws LocalizedException
     */
    protected function _initSelect(): Collection
    {
        parent::_initSelect();
        $businessNameAttributeId = $this->getAttributeIdByCode('business_name', $this->getCustomerEntityTypeId());

        $industryAttributeId = $this->getAttributeIdByCode('industry', $this->getCustomerEntityTypeId());

        $urlKeyAttributeId = $this->getAttributeIdByCode('url_key');

        $this->getSelect()
            ->joinLeft(
                ['so' => $this->getTable('sales_order')],
                'main_table.order_id = so.entity_id',
                ['increment_id', 'shipping_amount' => new \Zend_Db_Expr('(`so`.`shipping_amount`/`so`.`total_qty_ordered`) * `main_table`.`qty_ordered`'), 'grand_total', 'customer_id']
            )
            ->joinLeft(
                ['mo' => $this->getTable('mirakl_offer')],
                'main_table.sku = mo.product_sku',
                ['offer_id', 'shop_id', 'shop_name']
            )
            ->joinLeft(
                ['at_business_name' => $this->getTable('customer_entity_varchar')],
                "(so.customer_id = at_business_name.entity_id) AND (at_business_name.attribute_id = '$businessNameAttributeId')",
                ['business_name' => 'at_business_name.value']
            )
            ->joinLeft(
                ['at_industry' => $this->getTable('customer_entity_varchar')],
                "(so.customer_id = at_industry.entity_id) AND (at_industry.attribute_id = '$industryAttributeId')",
                ['industry' => 'at_industry.value']
            )
            ->joinLeft(
                ["soa" => "sales_order_address"],
                'so.entity_id = soa.parent_id AND soa.address_type="shipping"',
                ['email', 'company', 'country_id', 'postcode', 'city', 'telephone', 'region']
            )
            ->joinLeft(
                ['at_url_key' => $this->getTable('catalog_product_entity_varchar')],
                "(main_table.product_id = at_url_key.entity_id) AND (at_url_key.attribute_id = '$urlKeyAttributeId') AND at_url_key.store_id = 0",
                ['url_key' => 'at_url_key.value']
            )
            ->where('main_table.parent_item_id is null')
            ->distinct()
            ->group('main_table.item_id');

        return $this;
    }

    /**
     * @param mixed $field
     * @param null $condition
     * @return AbstractDb|Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == "created_at") {
            $field = "main_table.created_at";
        }
        if ($field == "customer_id") {
            $field = "so.customer_id";
        }
        if ($field == "business_name") {
            $field = "at_business_name.value";
        }
        if ($field == "industry") {
            $field = "at_industry.value";
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations(): AggregationInterface
    {
        return $this->aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations): Collection
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Get search criteria.
     *
     * @return SearchCriteriaInterface|null
     */
    public function getSearchCriteria(): ?SearchCriteriaInterface
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria = null): Collection
    {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTotalCount($totalCount): Collection
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param ExtensibleDataInterface[] $items
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setItems(array $items = null): Collection
    {
        return $this;
    }
}
