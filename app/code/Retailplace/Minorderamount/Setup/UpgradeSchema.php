<?php

namespace Retailplace\Minorderamount\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                $installer->getTable('mirakl_shop'),
                'min-order-amount',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 255,
                    'nullable' => true,
                    'comment' => 'min-order-amount'
                ]
            );
        }
        $installer->endSetup();
    }
}
