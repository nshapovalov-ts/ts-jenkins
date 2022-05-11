<?php
/**
 * A Magento 2 module named Retailplace/Performance
 * Copyright (C) 2019
 *
 * This file included in Retailplace/Performance is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Retailplace\Performance\Setup;

use Magento\Catalog\Model\ResourceModel\Product\Gallery;
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
        if (version_compare($context->getVersion(), "1.0.1", "<")) {
            $setup->startSetup();
            $setup->getConnection()->addColumn(
                $setup->getTable(Gallery::GALLERY_TABLE), 'is_cached', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'Is Image Cached'                ]
            );
            $setup->endSetup();
        }
    }
}