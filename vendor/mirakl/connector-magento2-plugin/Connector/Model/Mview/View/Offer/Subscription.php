<?php
namespace Mirakl\Connector\Model\Mview\View\Offer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\CollectionInterface;
use Magento\Framework\Mview\ViewInterface;

class Subscription extends \Magento\Framework\Mview\View\Subscription
{
    /**
     * @var EntityMetadata
     */
    protected $entityMetadata;

    /**
     * Save state of Subscription for build statement for retrieving entity id value
     *
     * @var array
     */
    private $statementState = [];

    /**
     * List of columns that can be updated in a subscribed table
     * for creating a new change log entry
     *
     * @var array
     */
    private $comparedUpdateColumns = [];

    /**
     * @param  ResourceConnection   $resource
     * @param  TriggerFactory       $triggerFactory
     * @param  CollectionInterface  $viewCollection
     * @param  ViewInterface        $view
     * @param  string               $tableName
     * @param  string               $columnName
     * @param  MetadataPool         $metadataPool
     * @param  array                $comparedUpdateColumns
     * @throws \Exception
     */
    public function __construct(
        ResourceConnection $resource,
        TriggerFactory $triggerFactory,
        CollectionInterface $viewCollection,
        ViewInterface $view,
        $tableName,
        $columnName,
        MetadataPool $metadataPool,
        $comparedUpdateColumns = []
    ) {
        parent::__construct(
            $resource,
            $triggerFactory,
            $viewCollection,
            $view,
            $tableName,
            $columnName,
            $comparedUpdateColumns
        );
        $this->comparedUpdateColumns = $comparedUpdateColumns;
        $this->entityMetadata = $metadataPool->getMetadata(ProductInterface::class);
    }

    /**
     * Build trigger statement for INSERT, UPDATE, DELETE events
     *
     * @param  string               $event
     * @param  ChangelogInterface   $changelog
     * @return string
     */
    protected function buildStatement($event, $changelog)
    {
        $triggerBody = '';

        switch ($event) {
            case Trigger::EVENT_INSERT:
            case Trigger::EVENT_UPDATE:
                $eventType = 'NEW';
                break;
            case Trigger::EVENT_DELETE:
                $eventType = 'OLD';
                break;
            default:
                return $triggerBody;
        }

        $entityIdHash = $this->entityMetadata->getIdentifierField()
            . $this->entityMetadata->getEntityTable()
            . $this->entityMetadata->getLinkField()
            . $event;

        if (!isset($this->statementState[$entityIdHash])) {
            $triggerBody = $this->buildEntityIdStatementByEventType($eventType);
            $this->statementState[$entityIdHash] = true;
        }

        $trigger = $this->buildStatementByEventType($changelog);
        if ($event == Trigger::EVENT_UPDATE) {
            $trigger = $this->addConditionsToTrigger($trigger);
        }
        $triggerBody .= $trigger;

        return $triggerBody;
    }

    /**
     * Adds quoted conditions to the trigger
     *
     * @param  string   $trigger
     * @return string
     */
    private function addConditionsToTrigger($trigger)
    {
        $tableName = $this->resource->getTableName($this->getTableName());
        if ($this->connection->isTableExists($tableName)
            && $describe = $this->connection->describeTable($tableName)
        ) {
            $columnNames = array_column($describe, 'COLUMN_NAME');
            $columnNames = array_intersect($columnNames, $this->comparedUpdateColumns);
            if ($columnNames) {
                $columns = [];
                foreach ($columnNames as $columnName) {
                    $columns[] = sprintf(
                        'NEW.%1$s <=> OLD.%1$s',
                        $this->connection->quoteIdentifier($columnName)
                    );
                }
                $trigger = sprintf(
                    "IF (%s) THEN %s END IF;",
                    implode(' OR ', $columns),
                    $trigger
                );
            }
        }

        return $trigger;
    }

    /**
     * Build trigger body
     *
     * @param  string   $eventType
     * @return string
     */
    private function buildEntityIdStatementByEventType($eventType)
    {
        return vsprintf(
            'SET @entity_id = (SELECT min(%1$s) FROM %2$s WHERE %3$s = %4$s.%5$s);',
            [
                $this->connection->quoteIdentifier(
                    $this->entityMetadata->getIdentifierField()
                ),
                $this->connection->quoteIdentifier(
                    $this->resource->getTableName($this->entityMetadata->getEntityTable())
                ),
                $this->connection->quoteIdentifier('sku'),
                $eventType,
                $this->connection->quoteIdentifier('product_sku')
            ]
        ) . PHP_EOL;
    }

    /**
     * Build sql statement for trigger
     *
     * @param  ChangelogInterface $changelog
     * @return string
     */
    private function buildStatementByEventType($changelog)
    {
        return vsprintf(
            'INSERT IGNORE INTO %1$s (%2$s) values(@entity_id);',
            [
                $this->connection->quoteIdentifier(
                    $this->resource->getTableName($changelog->getName())
                ),
                $this->connection->quoteIdentifier(
                    $changelog->getColumnName()
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        if (!$this->connection->isTableExists('mirakl_offer')) {
            return $this;
        }

        return parent::create();
    }

    /**
     * {@inheritdoc}
     */
    public function remove()
    {
        if (!$this->connection->isTableExists('mirakl_offer')) {
            return $this;
        }

        return parent::remove();
    }
}
