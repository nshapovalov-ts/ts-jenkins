<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema create tables needed for module
 */
class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $pageTableName = $installer->getTable('magefan_cms_display_rule_page');
        $blockTableName = $installer->getTable('magefan_cms_display_rule_block');
        /**
         * Create table 'magefan_cms_display_rule_page'
         */
        $table = $installer->getConnection()->newTable(
            $pageTableName
        )->addColumn(
            'page_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'primary' => true],
            'Page Id'
        )->addColumn(
            'group_id',
            Table::TYPE_TEXT,
            64,
            ['nullable' => true],
            'Group Id'
        )->addColumn(
            'start_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Start Date'
        )->addColumn(
            'finish_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Finish Date'
        )->addColumn(
            'time_from',
            Table::TYPE_INTEGER,
            10,
            ['nullable' => false],
            'Time From'
        )->addColumn(
            'time_to',
            Table::TYPE_INTEGER,
            10,
            ['nullable' => false],
            'Time To'
        )->addColumn(
            'days_of_week',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Days Of Week'
        )->addColumn(
            'conditions_serialized',
            Table::TYPE_TEXT,
            '2M',
            [],
            'Conditions'
        )->addColumn(
            'another_cms',
            Table::TYPE_INTEGER,
            6,
            ['nullable' => false],
            'Another CMS Id'
        )->setComment(
            'Magefan CMS Display Rules Page Table'
        );

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'magefan_cms_display_rule_block'
         */
        $table = $installer->getConnection()->newTable(
            $blockTableName
        )->addColumn(
            'block_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'primary' => true],
            'Block Id'
        )->addColumn(
            'group_id',
            Table::TYPE_TEXT,
            64,
            [],
            'Group Id'
        )->addColumn(
            'start_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Start Date'
        )->addColumn(
            'finish_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Finish Date'
        )->addColumn(
            'time_from',
            Table::TYPE_INTEGER,
            10,
            ['nullable' => false],
            'Time From'
        )->addColumn(
            'time_to',
            Table::TYPE_INTEGER,
            10,
            ['nullable' => false],
            'Time To'
        )->addColumn(
            'days_of_week',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Days Of Week'
        )->addColumn(
            'conditions_serialized',
            Table::TYPE_TEXT,
            '2M',
            [],
            'Conditions'
        )->addColumn(
            'another_cms',
            Table::TYPE_INTEGER,
            6,
            ['nullable' => false],
            'Another CMS Id'
        )->addColumn(
            'secret',
            Table::TYPE_TEXT,
            64,
            [],
            'Secret'
        )->setComment(
            'Magefan CMS Display Rules Block Table'
        );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
