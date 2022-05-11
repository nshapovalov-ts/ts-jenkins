<?php
namespace Mirakl\Connector\Setup;

use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Ddl\TriggerFactory;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var TriggerFactory
     */
    protected $triggerFactory;

    /**
     * @param TriggerFactory $triggerFactory
     */
    public function __construct(TriggerFactory $triggerFactory)
    {
        $this->triggerFactory = $triggerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $connection = $setup->getConnection();
        $offerTableName = $setup->getTable('mirakl_offer');

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            // Add allow-quote-requests field
            $connection->addColumn($offerTableName, 'allow_quote_requests', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 5,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Allow Quote Requests',
            ]);

            // Add price-ranges field
            $connection->addColumn($offerTableName, 'price_ranges', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Price Ranges',
            ]);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $connection->changeColumn($offerTableName, 'state_id', 'state_code', [
                'type'     => Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => false,
                'default'  => 11,
                'comment'  => 'State Code',
            ]);

            $connection->changeColumn($offerTableName, 'currency_code', 'currency_iso_code', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 3,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Currency ISO Code',
            ]);

            $connection->changeColumn($offerTableName, 'lead_time_to_ship', 'leadtime_to_ship', [
                'type'     => Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Lead Time to Ship',
            ]);

            $connection->addColumn($offerTableName, 'additional_info', [
                'type'     => Table::TYPE_TEXT,
                'length'   => Table::MAX_TEXT_SIZE,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Additional Info',
            ]);
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            $connection->addColumn($offerTableName, 'min_order_quantity', [
                'type'     => Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default'  => null,
                'after'    => 'price_ranges',
                'comment'  => 'Min Order Quantity',
            ]);

            $connection->addColumn($offerTableName, 'max_order_quantity', [
                'type'     => Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default'  => null,
                'after'    => 'min_order_quantity',
                'comment'  => 'Max Order Quantity',
            ]);

            $connection->addColumn($offerTableName, 'package_quantity', [
                'type'     => Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'default'  => null,
                'after'    => 'max_order_quantity',
                'comment'  => 'Package Quantity',
            ]);

            $connection->addColumn($offerTableName, 'product_tax_code', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'default'  => null,
                'after'    => 'package_quantity',
                'comment'  => 'Product Tax Code',
            ]);
        }

        if (version_compare($context->getVersion(), '1.2.2', '<')) {
            $configTable = $setup->getTable('core_config_data');
            $updateConfig = [
                'mirakl_mci/general/translation_store' => 'mirakl_connector/general/translation_store',
                'mirakl_mci/general/locale_codes_for_labels_translation' => 'mirakl_connector/general/locale_codes_for_labels_translation',
            ];

            foreach ($updateConfig as $oldKey => $newKey) {
                $where = ['path = ?' => $oldKey];
                $bind = ['path' => $newKey];
                $connection->update($configTable, $bind, $where);
                $connection->delete($configTable, $where);
            }
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            // Modify the leadtime to ship type from int to string in order to handle empty values correctly
            $setup->getConnection()
                ->modifyColumn($offerTableName, 'leadtime_to_ship', [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 3,
                    'nullable' => true,
                    'default'  => null,
                    'comment'  => 'Leadtime To Ship',
                ]);
        }

        $this->createTriggers($setup);

        $setup->endSetup();
    }

    /**
     * @param   SchemaSetupInterface    $setup
     * @return  void
     */
    private function createTriggers(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        // Handle multi inventory sources for operator products without stock and with active offers (force stock status to 1)
        $inventorySourceItemTable = $setup->getTable('inventory_source_item');
        if ($connection->isTableExists($inventorySourceItemTable)) {
            $triggerName = 'mirakl_multi_inventory_product_with_offers';
            $trigger = $this->triggerFactory->create()
                ->setName($triggerName)
                ->setTime(Trigger::TIME_BEFORE)
                ->setEvent(Trigger::EVENT_INSERT)
                ->setTable($inventorySourceItemTable);

            $statement = <<<SQL
IF NEW.status = 0 THEN
    SET @count_offers = (
        SELECT COUNT(offer_id)
        FROM {$connection->quoteIdentifier($setup->getTable('mirakl_offer'))} AS offers
        INNER JOIN {$connection->quoteIdentifier($setup->getTable('mirakl_shop'))} AS shops ON (offers.shop_id = shops.id)
        WHERE offers.active = 'true' AND shops.state = {$connection->quote(\Mirakl\Core\Model\Shop::STATE_OPEN)} AND offers.product_sku = NEW.sku
    );
    IF @count_offers > 0 THEN
        SET NEW.status = 1;
    END IF;
END IF;
SQL;

            $trigger->addStatement($statement);

            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);
        }

        // Handle product stock status index for operator products without stock and with active offers (force stock status to 1)
        $stockStatusTable = $setup->getTable('cataloginventory_stock_status');
        if ($connection->isTableExists($stockStatusTable)) {
            $triggerName = 'mirakl_stock_product_with_offers';
            $trigger = $this->triggerFactory->create()
                ->setName($triggerName)
                ->setTime(Trigger::TIME_BEFORE)
                ->setEvent(Trigger::EVENT_INSERT)
                ->setTable($stockStatusTable);

            $statement = <<<SQL
IF NEW.stock_status = 0 THEN
    SET @count_offers = (
        SELECT COUNT(offer_id)
        FROM {$connection->quoteIdentifier($setup->getTable('mirakl_offer'))} AS offers
        INNER JOIN {$connection->quoteIdentifier($setup->getTable('mirakl_shop'))} AS shops ON (offers.shop_id = shops.id)
        INNER JOIN {$connection->quoteIdentifier($setup->getTable('catalog_product_entity'))} AS products ON (products.entity_id = NEW.product_id)
        WHERE offers.active = 'true' AND shops.state = {$connection->quote(\Mirakl\Core\Model\Shop::STATE_OPEN)} AND offers.product_sku = products.sku
    );
    IF @count_offers > 0 THEN
        SET NEW.stock_status = 1;
    END IF;
END IF;
SQL;

            $trigger->addStatement($statement);

            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);
        }
    }
}
