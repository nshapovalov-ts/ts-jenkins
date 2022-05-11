<?php
namespace Retailplace\Aghaseller\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $eavTable = $installer->getTable('mirakl_shop');

        $columns = [
            'agha_seller' => [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'nullable' => false,
                'length'   => '11',
                'comment'  => 'Is Agha Seller',
                'default'  => '0',
            ],

        ];

        $connection = $installer->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($eavTable, $name, $definition);
        }

        $installer->endSetup();
    }
}
