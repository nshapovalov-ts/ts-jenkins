<?php 
namespace Retailplace\Offerdetail\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;

        $installer->startSetup();
        if (version_compare($context->getVersion(), "1.0.0", "<")) {
        //Your upgrade script
        }
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('mirakl_offer'),
                'created_at',
                [
                    'type' =>  \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => false, 
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
                    'comment' => 'Created At'
                ]
            );
            $installer->getConnection()->addColumn(
                $installer->getTable('mirakl_offer'),
                'updated_at',
                [
                    'type' =>  \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    'nullable' => false, 
                    'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                    'comment' => 'Updated At'
                ]
            );
        }
        $installer->endSetup();
    }
}