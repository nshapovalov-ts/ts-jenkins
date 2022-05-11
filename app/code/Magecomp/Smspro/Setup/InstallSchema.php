<?php
namespace Magecomp\Smspro\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
		$installer = $setup;
        $installer->startSetup();

		$table = $installer->getConnection()
            ->newTable($installer->getTable('sms_verify'))
->addColumn(
                'sms_verify_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
			)
->addColumn(
                'mobile_number',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                15,
                ['nullable' => false],
                'Mobile Number'
            )
->addColumn(
                'otp',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                12,
                ['nullable' => false],
                'Otp'
            )
->addColumn(
                'isverify',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Is Verify'
            );
            $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
            ->newTable($installer->getTable('phonebook'))
            ->addColumn(
                'phonebook_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                15,
                ['nullable' => false],
                'Phonebook Name'
            )
            ->addColumn(
                'mobile',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                12,
                ['nullable' => false],
                'Phonebook Mobile Number'
            );
        $installer->getConnection()->createTable($table);
    }
}