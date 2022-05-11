<?php
namespace Mirakl\Event\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $connection = $setup->getConnection();

            // Change import_id field to varchar because of MCM returning a string for synchro id
            $connection->modifyColumn(
                $setup->getTable('mirakl_event'),
                'import_id',
                [
                    'type'   => Table::TYPE_TEXT,
                    'length' => 36,
                ]
            );

            // Add missing index on import id column
            $connection->addIndex(
                $setup->getTable('mirakl_event'),
                $setup->getIdxName('mirakl_event', ['import_id']),
                ['import_id']
            );
        }

        $setup->endSetup();
    }
}
