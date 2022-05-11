<?php
namespace Retailplace\MiraklConnector\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $offerTableName = $setup->getTable('mirakl_offer');
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $connection->changeColumn(
                $offerTableName,
                'clearance',
                'clearance',
                [
                    'type'     => Table::TYPE_BOOLEAN,
                    'default'  => 0,
                    'nullable' => false,
                    'comment'  => 'Clearance'
                ]
            );

            $connection->addIndex(
                $setup->getTable('mirakl_offer'),
                $setup->getIdxName('mirakl_offer', ['clearance']),
                ['clearance']
            );
        }
        $setup->endSetup();
    }
}
