<?php
namespace Retailplace\MiraklConnector\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $offerTableName = $setup->getTable('mirakl_offer');

        $connection->addColumn($offerTableName, 'clearance', [
            'type'     => Table::TYPE_INTEGER,
            'length'   => 6,
            'nullable' => false,
            'default'  => 0,
            'comment'  => 'Clearance',
            'after'    => 'product_tax_code'
        ]);

        $setup->endSetup();
    }
}
