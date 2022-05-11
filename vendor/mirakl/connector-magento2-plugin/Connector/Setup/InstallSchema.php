<?php
namespace Mirakl\Connector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Create table for entity 'mirakl/offer'
        $tableName = $setup->getTable('mirakl_offer');
        $tableComment = 'Mirakl Offers';
        $columns = [
            'offer_id' => [
                'type' => Table::TYPE_INTEGER,
                'size' => 11,
                'options' => ['nullable' => false, 'primary' => true],
                'comment' => 'Offer Id',
            ],
            'product_sku' => [
                'type' => Table::TYPE_TEXT,
                'size' => 64,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Product SKU',
            ],
            'min_shipping_price' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '19,2',
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Min Shipping Price',
            ],
            'min_shipping_price_additional' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '19,2',
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Min Shipping Price Additional',
            ],
            'min_shipping_zone' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Min Shipping Zone',
            ],
            'min_shipping_type' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Min Shipping Type',
            ],
            'price' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '19,2',
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Price',
            ],
            'total_price' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '19,2',
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Total Price',
            ],
            'price_additional_info' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => true, 'default' => ''],
                'comment' => 'Price Additional Info',
            ],
            'quantity' => [
                'type' => Table::TYPE_INTEGER,
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Quantity',
            ],
            'description' => [
                'type' => Table::TYPE_TEXT,
                'size' => 65536,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Description',
            ],
            'state_id' => [
                'type' => Table::TYPE_INTEGER,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 11],
                'comment' => 'State Id',
            ],
            'shop_id' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'comment' => 'Shop Id',
            ],
            'shop_name' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Shop Name',
            ],
            'professional' => [
                'type' => Table::TYPE_TEXT,
                'size' => 5,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Professional',
            ],
            'premium' => [
                'type' => Table::TYPE_TEXT,
                'size' => 5,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Premium',
            ],
            'logistic_class' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Logistic Class',
            ],
            'active' => [
                'type' => Table::TYPE_TEXT,
                'size' => 5,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Is Active',
            ],
            'favorite_rank' => [
                'type' => Table::TYPE_INTEGER,
                'size' => 11,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Favorite Rank',
            ],
            'channels' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Channels',
            ],
            'deleted' => [
                'type' => Table::TYPE_TEXT,
                'size' => 5,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Deleted',
            ],
            'origin_price' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '19,2',
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Origin Price',
            ],
            'discount_start_date' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Discount Start Date',
            ],
            'discount_end_date' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Discount End Date',
            ],
            'available_start_date' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Available Start Date',
            ],
            'available_end_date' => [
                'type' => Table::TYPE_DATETIME,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Available End Date',
            ],
            'discount_price' => [
                'type' => Table::TYPE_DECIMAL,
                'size' => '19,2',
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Discount Price',
            ],
            'currency_code' => [
                'type' => Table::TYPE_TEXT,
                'size' => 3,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Currency Code',
            ],
            'discount_ranges' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Discount Ranges',
            ],
            'lead_time_to_ship' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['nullable' => true, 'default' => null],
                'comment' => 'Lead Time To Ship',
            ],
        ];

        $indexes = ['product_sku', 'active'];

        // No foreign key for this table
        $foreignKeys = [];

        $this->installNewTable($setup, $tableName, $columns, $tableComment, $indexes, $foreignKeys);

        $setup->endSetup();
    }

    /**
     * @param   SchemaSetupInterface    $setup
     * @param   string                  $tableName
     * @param   array                   $columns
     * @param   string                  $tableComment
     * @param   array|null              $indexes
     * @param   array|null              $foreignKeys
     */
    public function installNewTable(SchemaSetupInterface $setup, $tableName, $columns, $tableComment, $indexes = null, $foreignKeys = null)
    {
        // Table creation
        $setup->getConnection()->dropTable($tableName);
        $table = $setup->getConnection()->newTable($tableName);
    
        // Columns creation
        foreach ($columns as $name => $values) {
            $table->addColumn(
                $name,
                $values['type'],
                isset($values['size']) ? $values['size'] : null,
                $values['options'],
                $values['comment']
            );
        }
    
        if (!empty($indexes)) {
            // Indexes creation
            foreach ($indexes as $index) {
                $table->addIndex(
                    $setup->getIdxName($tableName, [$index]),
                    [$index]
                );
            }
        }
    
        if (!empty($foreignKeys)) {
            // Foreign keys creation
            foreach ($foreignKeys as $column => $foreignKey) {
                $table->addForeignKey(
                    $setup->getFkName($tableName, $column, $foreignKey['ref_table'], $foreignKey['ref_column']),
                    $column,
                    $foreignKey['ref_table'],
                    $foreignKey['ref_column'],
                    $foreignKey['on_delete']
                );
            }
        }
    
        // Table comment
        $table->setComment($tableComment);
    
        // Execute SQL to create the table
        $setup->getConnection()->createTable($table->setOption('type', 'MyISAM'));
    }
}