<?php
namespace Mirakl\Event\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Create table for entity 'mirakl/event'
        $setup->getConnection()->dropTable($setup->getTable('mirakl_event'));
        $table = $setup->getConnection()->newTable($setup->getTable('mirakl_event'))
            ->addColumn('id', Table::TYPE_INTEGER, null, [
                'identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true
            ], 'Event Id')
            ->addColumn('code', Table::TYPE_TEXT, 100, [], 'Code')
            ->addColumn('action', Table::TYPE_SMALLINT, null, [
                'unsigned' => true, 'nullable' => false, 'default' => '0'
            ], 'Action')
            ->addColumn('type', Table::TYPE_SMALLINT, null, [
                'unsigned' => true, 'nullable' => false, 'default' => '0'
            ], 'Type')
            ->addColumn('status', Table::TYPE_TEXT, 50, ['nullable' => false, 'default'  => 'waiting'], 'Status')
            ->addColumn('csv_data', Table::TYPE_TEXT, null, [], 'CSV Data')
            ->addColumn('process_id', Table::TYPE_INTEGER, null, ['unsigned' => true], 'Process Id')
            ->addColumn('line', Table::TYPE_INTEGER, null, ['unsigned' => true], 'Line')
            ->addColumn('import_id', Table::TYPE_INTEGER, null, ['unsigned' => true], 'Import Id')
            ->addColumn('message', Table::TYPE_TEXT, 255, ['default' => null], 'Message')
            ->addColumn('created_at', Table::TYPE_DATETIME, null, ['default' => null], 'Created At')
            ->addColumn('updated_at', Table::TYPE_DATETIME, null, ['default' => null], 'Updated At')
            ->addIndex($setup->getIdxName('mirakl_event', ['code']), ['code'])
            ->addIndex($setup->getIdxName('mirakl_event', ['type']), ['type'])
            ->addIndex($setup->getIdxName('mirakl_event', ['status']), ['status'])
            ->setComment('Mirakl Event');

        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}
