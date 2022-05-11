<?php
/**
 * Retailplace_Misspell
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Misspell\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Mirasvit\Misspell\Helper\Text as TextHelper;
use Retailplace\Misspell\Model\Search\Engine;
use Zend_Db_Statement_Exception;
use Mirasvit\Misspell\Provider\Indexer as ProviderIndexer;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

/**
 * Indexer Class
 */
class Indexer extends ProviderIndexer
{
    /**
     * @var string
     */
    const CONFIG_PATH_EXCLUDE_ATTRIBUTES = 'misspell/general/exclude_attributes';

    /**
     * @var array
     */
    private $allowedTables = [
        'mst_searchindex_',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar',
        'catalog_category_entity_text',
        'catalog_category_entity_varchar',
    ];

    /**
     * @var array
     */
    private $disallowedTables = [
        'mst_searchindex_mage_catalogsearch_query',
    ];

    /**
     * @var string[]
     */
    private $eavAttributeTables = [
        'catalog_product_entity_text',
        'catalog_product_entity_varchar'
    ];

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var string
     */
    const INDEX_NAME = 'misspell_index';

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var
     */
    private $excludeAttributes;

    /**
     * Indexer constructor.
     *
     * @param ResourceConnection $resource
     * @param TextHelper $textHelper
     * @param Engine $engine
     * @param CollectionFactory $collectionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection   $resource,
        TextHelper           $textHelper,
        Engine               $engine,
        CollectionFactory    $collectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($resource, $textHelper);

        $this->resource = $resource;
        $this->connection = $this->resource->getConnection();
        $this->engine = $engine;
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->batchSize = 1000;
    }

    /**
     * Reindex
     *
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function reindex()
    {
        $indexName = $this->getIndexName();
        $this->engine->cleanDocuments($indexName);

        foreach ($this->getTables() as $table => $columns) {
            if (!count($columns)) {
                continue;
            }

            foreach ($columns as $idx => $col) {
                $columns[$idx] = '`' . $col . '`';
            }

            $select = $this->connection->select();
            $fromColumns = new \Zend_Db_Expr("CONCAT_WS(' '," . implode(',', $columns) . ") as data_index");
            $select->from($table, $fromColumns);

            if (in_array($table, $this->eavAttributeTables)) {
                $attributes = $this->getSearchableAttribute();
                $select->where("attribute_id in (?)", $attributes);
            }

            $results = $this->getTablesData($select);
            $rows = [];

            foreach ($results as $word => $freq) {
                $key = hash('crc32', $word);

                $rows[$key] = [
                    'keyword' => $word
                ];

                if (count($rows) > $this->batchSize) {
                    $this->engine->saveDocuments($indexName, $rows);

                    $rows = [];
                }
            }

            if (count($rows) > 0) {
                $this->engine->saveDocuments($indexName, $rows);
            }
        }
    }

    /**
     * Get Tables Data
     *
     * @param Select $select
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    private function getTablesData(Select $select): array
    {
        $offset = 0;
        $results = [];
        while (true) {
            $select->limit('10000', $offset);
            $result = $this->connection->query($select);
            $rows = $result->fetchAll();
            if (!$rows) {
                return $results;
            }

            foreach ($rows as $row) {
                $data = $row['data_index'];
                if (!empty($data)) {
                    $this->split($data, $results);
                }
            }

            $offset += 10000;
        }
    }

    /**
     * List of tables that follow allowedTables, disallowedTables conditions
     *
     * @return array
     */
    protected function getTables(): array
    {
        $result = [];
        $tables = $this->connection->getTables();

        foreach ($tables as $table) {
            $isAllowed = false;

            foreach ($this->allowedTables as $allowedTable) {
                if (mb_strpos($table, $allowedTable) !== false) {
                    $isAllowed = true;
                }
            }

            foreach ($this->disallowedTables as $disallowedTable) {
                if (mb_strpos($table, $disallowedTable) !== false) {
                    $isAllowed = false;
                }
            }

            if (!$isAllowed) {
                continue;
            }

            $result[$table] = $this->getTextColumns($table);
        }

        return $result;
    }

    /**
     * Get Text Columns
     *
     * @param string $table Database table name
     * @return array list of columns with text type
     */
    protected function getTextColumns($table): array
    {
        $result = [];
        $allowedTypes = ['text', 'varchar', 'mediumtext', 'longtext'];
        $columns = $this->connection->describeTable($table);

        foreach ($columns as $column => $info) {
            if (in_array($info['DATA_TYPE'], $allowedTypes)) {
                $result[] = $column;
            }
        }

        return $result;
    }

    /**
     * Get Index Name
     *
     * @return string
     */
    private function getIndexName(): string
    {
        return self::INDEX_NAME;
    }

    /**
     * Get Searchable Attribute
     *
     * @return array
     */
    public function getSearchableAttribute(): array
    {
        if ($this->attributes !== null) {
            return $this->attributes;
        }

        $this->attributes = [];
        $searchableAttributes = $this->collectionFactory
            ->create()
            ->addDisplayInAdvancedSearchFilter()
            ->getItems();

        foreach ($searchableAttributes as $searchableAttribute) {
            if (!$this->isExcludeAttribute($searchableAttribute->getAttributeCode())) {
                $this->attributes[] = $searchableAttribute->getId();
            }
        }

        return $this->attributes;
    }

    /**
     * Is Exclude Attribute
     *
     * @param $attributeName
     * @return bool
     */
    private function isExcludeAttribute($attributeName): bool
    {
        if ($this->excludeAttributes === null) {
            $excludeAttributes = $this->scopeConfig->getValue(self::CONFIG_PATH_EXCLUDE_ATTRIBUTES);
            $this->excludeAttributes = [];
            if (!$excludeAttributes) {
                return false;
            }

            $excludeAttributes = explode(',', $excludeAttributes);
            $excludeAttributes = array_map(function ($value) {
                return trim($value);
            }, $excludeAttributes);

            $this->excludeAttributes = $excludeAttributes;
        }

        return in_array($attributeName, $this->excludeAttributes);
    }
}
