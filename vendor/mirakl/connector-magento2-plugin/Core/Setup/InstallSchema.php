<?php
namespace Mirakl\Core\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Create table for entity 'mirakl/shop'
        $tableName = $setup->getTable('mirakl_shop');
        $tableComment = 'Mirakl Shops';
        $columns = [
            'id' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'comment' => 'Shop Id',
            ],
            'name' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Shop Name',
            ],
            'eav_option_id' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['nullable' => true],
                'comment' => 'EAV Option Id',
            ],
            'state' => [
                'type' => Table::TYPE_TEXT,
                'size' => 20,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Shop State',
            ],
            'date_created' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Creation Date',
            ],
            'description' => [
                'type' => Table::TYPE_TEXT,
                'size' => 65536,
                'options' => ['nullable' => true, 'default' => ''],
                'comment' => 'Shop Description',
            ],
            'logo' => [
                'type' => Table::TYPE_TEXT,
                'size' => 65536,
                'options' => ['nullable' => true, 'default' => ''],
                'comment' => 'Shop Logo',
            ],
            'free_shipping' => [
                'type' => Table::TYPE_BOOLEAN,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Is Free Shipping',
            ],
            'professional' => [
                'type' => Table::TYPE_BOOLEAN,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Is Professional',
            ],
            'premium' => [
                'type' => Table::TYPE_BOOLEAN,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Is Premium',
            ],
            'closed_from' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Closed From',
            ],
            'closed_to' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Closed To',
            ],
            'grade' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '5,2',
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Grade'
            ],
            'evaluations_count'=> [
                'type' => Table::TYPE_INTEGER,
                'options' => ['nullable' => false, 'default' => '0'],
                'comment' => 'Evaluations count'
            ],
            'additional_info' => [
                'type' => Table::TYPE_TEXT,
                'size' => 2048,
                'options' => ['nullable' => true, 'default' => ''],
                'comment' => 'Additional Information',
            ],

        ];

        // No index for this table
        $indexes =  ['state'];

        // No foreign keys for this table
        $foreignKeys = [];

        // We can use the parameters above to create our table
        $this->installNewTable($setup, $tableName, $columns, $tableComment, $indexes, $foreignKeys);

        // Create table for entity 'mirakl/shipping_zone'
        $table = $setup->getConnection()
            ->newTable($setup->getTable('mirakl_shipping_zone'))
            ->addColumn('id', Table::TYPE_INTEGER, null, [
                'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true
            ], 'Zone Id')
            ->addColumn('code', Table::TYPE_TEXT, 255, ['nullable' => false], 'Zone Code')
            ->addColumn('is_active', Table::TYPE_SMALLINT, null, ['nullable' => false, 'default' => '1'], 'Is Active')
            ->addColumn('conditions_serialized', Table::TYPE_TEXT, '2M', [], 'Conditions Serialized')
            ->addColumn('sort_order', Table::TYPE_INTEGER, null, [
                'unsigned' => true, 'nullable' => false, 'default' => '0'
            ], 'Sort Order')
            ->setComment('Mirakl Shipping Zones');

        $setup->getConnection()->createTable($table);

        // Create table for entity 'mirakl/shipping_zone_store'
        $table = $setup->getConnection()
            ->newTable($setup->getTable('mirakl_shipping_zone_store'))
            ->addColumn('zone_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'
            ], 'Shipping Zone Id')
            ->addColumn('store_id', Table::TYPE_SMALLINT, null, [
                'unsigned' => true, 'nullable' => false, 'primary' => true, 'default' => '0'
            ], 'Store Id')
            ->addIndex($setup->getIdxName('mirakl_shipping_zone_store', ['store_id']), ['store_id'])
            ->addForeignKey(
                $setup->getFkName('mirakl_shipping_zone_store', 'zone_id', 'mirakl_shipping_zone', 'id'),
                'zone_id',
                $setup->getTable('mirakl_shipping_zone'),
                'id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName('mirakl_shipping_zone_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Mirakl Shipping Zone Stores');

        $setup->getConnection()->createTable($table);

        // Create table for entity 'mirakl/mirakl_offer_state'
        $table = $setup->getConnection()
            ->newTable($setup->getTable('mirakl_offer_state'))
            ->addColumn('id', Table::TYPE_INTEGER, null, [
                'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true
            ], 'State Id')
            ->addColumn('name', Table::TYPE_TEXT, 255, ['nullable' => false], 'State Name')
            ->addColumn('eav_option_id', Table::TYPE_INTEGER, null, ['nullable' => true], 'EAV Option Id')
            ->addColumn('sort_order', Table::TYPE_SMALLINT, null, [
                'unsigned' => true, 'nullable' => false, 'default' => '0'
            ], 'Sort Order')
            ->setComment('Mirakl Offer States');

        $setup->getConnection()->createTable($table);

        // Create table for entity 'mirakl/document_type'
        $tableName = $setup->getTable('mirakl_document_type');
        $tableComment = 'Mirakl Dcoument Types';
        $columns = [
            'id' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'comment' => 'Document Type Id',
            ],
            'label' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Document Type Label',
            ],
            'code' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Document Type Code',
            ],
        ];

        // No index for this table
        $indexes = [];

        // No foreign keys for this table
        $foreignKeys = [];

        // We can use the parameters above to create our table
        $this->installNewTable($setup, $tableName, $columns, $tableComment, $indexes, $foreignKeys);

        // End Setup
        $setup->endSetup();
    }

    /**
     * @param   SchemaSetupInterface    $setup
     * @param   string                  $tableName
     * @param   array                   $columns
     * @param   string                  $tableComment
     * @param   null                    $indexes
     * @param   null                    $foreignKeys
     */
    public function installNewTable(
        SchemaSetupInterface $setup, $tableName, $columns, $tableComment, $indexes = null, $foreignKeys = null
    ) {
        // Table creation
        $table = $setup->getConnection()->newTable($tableName);

        // Columns creation
        foreach ($columns as $name => $values) {
            $table->addColumn(
                $name,
                $values['type'],
                isset($values['size']) ? $values['size'] : null,
                $values['options'],
                $values['comment']
            );
        }

        if (!empty($indexes)) {
            // Indexes creation
            foreach ($indexes as $index) {
                $table->addIndex(
                    $setup->getIdxName($tableName, [$index]),
                    [$index]
                );
            }
        }

        if (!empty($foreignKeys)) {
            // Foreign keys creation
            foreach ($foreignKeys as $column => $foreignKey) {
                $table->addForeignKey(
                    $setup->getFkName($tableName, $column, $foreignKey['ref_table'], $foreignKey['ref_column']),
                    $column,
                    $foreignKey['ref_table'],
                    $foreignKey['ref_column'],
                    $foreignKey['on_delete']
                );
            }
        }

        // Table comment
        $table->setComment($tableComment);

        // Execute SQL to create the table
        $setup->getConnection()->createTable($table);
    }
}
