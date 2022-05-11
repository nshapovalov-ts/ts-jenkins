<?php
namespace Mirakl\Connector\Plugin\Indexer\Inventory;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;

class IndexStructurePlugin
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @var TriggerFactory
     */
    private $triggerFactory;

    /**
     * Need to use object manager in order to keep compatibility
     * with version 2.2.x of Magento
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param   ResourceConnection      $resourceConnection
     * @param   TriggerFactory          $triggerFactory
     * @param   ObjectManagerInterface  $objectManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TriggerFactory $triggerFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->triggerFactory = $triggerFactory;
        $this->objectManager = $objectManager;

        $this->indexNameResolver = $this->objectManager
            ->get('Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface');
    }

    /**
     * @param   IndexStructure  $subject
     * @param   null            $result
     * @param   IndexName       $indexName
     * @param   string          $connectionName
     */
    public function afterCreate(IndexStructure $subject, $result, IndexName $indexName, $connectionName)
    {
        $pattern = InventoryIndexer::INDEXER_ID . '_stock_\d+';
        $tableName = $this->indexNameResolver->resolveName($indexName);

        if (1 === preg_match("#$pattern#", $tableName)) {
            $connection = $this->resourceConnection->getConnection($connectionName);

            $trigger = $this->triggerFactory->create()
                ->setName('mirakl_' . $tableName . '_product_with_offers')
                ->setTime(Trigger::TIME_BEFORE)
                ->setEvent(Trigger::EVENT_INSERT)
                ->setTable($this->resourceConnection->getTableName($tableName));

            $statement = <<<SQL
IF NEW.is_salable = 0 THEN
    SET @count_offers = (
        SELECT COUNT(offer_id)
        FROM {$connection->quoteIdentifier($this->resourceConnection->getTableName('mirakl_offer'))} AS offers
        INNER JOIN {$connection->quoteIdentifier($this->resourceConnection->getTableName('mirakl_shop'))} AS shops ON (offers.shop_id = shops.id)
        WHERE offers.active = 'true' AND shops.state = {$connection->quote(\Mirakl\Core\Model\Shop::STATE_OPEN)} AND offers.product_sku = NEW.sku
    );
    IF @count_offers > 0 THEN
        SET NEW.is_salable = 1;
    END IF;
END IF;
SQL;
            $trigger->addStatement($statement);

            $connection->dropTrigger($trigger->getName());
            $connection->createTrigger($trigger);
        }
    }
}