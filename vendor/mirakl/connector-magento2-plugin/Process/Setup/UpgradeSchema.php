<?php
namespace Mirakl\Process\Setup;

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

            // Change synchro_id field to varchar because of MCM returning a string for synchro id
            $connection->modifyColumn(
                $setup->getTable('mirakl_process'),
                'synchro_id',
                [
                    'type'   => Table::TYPE_TEXT,
                    'length' => 36,
                ]
            );
        }

        $setup->endSetup();
    }
}
