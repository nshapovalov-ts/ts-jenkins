<?php
namespace Mirakl\Connector\Helper\Offer;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;

class MultiSourceInventory extends AbstractHelper
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * Need to use object manager in order to keep compatibility
     * with version 2.2.x of Magento
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param   Context                 $context
     * @param   ResourceConnection      $resource
     * @param   ObjectManagerInterface  $objectManager
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->objectManager = $objectManager;
    }

    /**
     * @param   array   $skus
     * @return  int
     */
    public function updateInStock(array $skus = [])
    {
        if (!$this->tableSourceItemsExists()) {
            return 0;
        }

        // Select SKU from all ACTIVE offers
        $select = $this->connection->select()
            ->from($this->connection->getTableName('mirakl_offer'), 'product_sku')
            ->where('active = ?', 'true')
            ->distinct(true);

        if (!empty($skus)) {
            $select->where('product_sku IN (?)', $skus);
        }

        // Set all sources stock to 1 for previous stock status = 0
        $updated = $this->connection->update(
            $this->connection->getTableName('inventory_source_item'),
            ['status' => 1],
            [sprintf('status = 0 AND quantity = 0 AND sku IN (%s)', $select)]
        );

        return $updated;
    }

    /**
     * @param   array   $skus
     * @return  int
     */
    public function updateOutOfStock(array $skus = [])
    {
        if (!$this->tableSourceItemsExists()) {
            return 0;
        }

        // Select SKU from all INACTIVE offers
        $select = $this->connection->select()
            ->from($this->connection->getTableName('mirakl_offer'), 'product_sku')
            ->where('active = ?', 'false')
            ->distinct(true);

        if (!empty($skus)) {
            $select->where('product_sku IN (?)', $skus);
        }

        // Set all sources stock to 0 for previous stock status = 1
        $updated = $this->connection->update(
            $this->connection->getTableName('inventory_source_item'),
            ['status' => 0],
            [sprintf('status = 1 AND quantity = 0 AND sku IN (%s)', $select)]
        );

        return $updated;
    }

    /**
     * @param   array   $skus
     */
    public function updateIndexes(array $skus = [])
    {
        if (!$this->tableSourceItemsExists()) {
            return;
        }

        // Retrieve source item ids from SKUS
        $sourceItemIds = $this->getSourceItemIdsBySkus($skus);

        if (!empty($sourceItemIds)) {
            /** @var \Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer $sourceItemIndexer */
            $sourceItemIndexer = $this->objectManager->get('Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer');
            $sourceItemIndexer->executeList($sourceItemIds);
        }
    }

    /**
     * @param   array   $skus
     * @return  array
     */
    protected function getSourceItemIdsBySkus(array $skus = [])
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName('inventory_source_item'), 'source_item_id');

        if (!empty($skus)) {
            $select->where('sku IN (?)', $skus);
        }

        return $this->connection->fetchCol($select);
    }

    /**
     * @return  bool
     */
    protected function tableSourceItemsExists()
    {
        $tableName = $this->connection->getTableName('inventory_source_item');

        return $this->connection->isTableExists($tableName);
    }
}
