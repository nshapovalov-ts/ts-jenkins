<?php
namespace Mirakl\Core\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Uninstall implements UninstallInterface
{
    /**
     * {@inheritdoc}
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $uninstaller = $setup;
        $uninstaller->startSetup();

        $this->deleteTable($setup, 'mirakl_shop');
        $this->deleteTable($setup, 'mirakl_offer_state');
        $this->deleteTable($setup, 'mirakl_shipping_zone_store');
        $this->deleteTable($setup, 'mirakl_shipping_zone');

        $uninstaller->endSetup();
    }

    /**
     * @param   SchemaSetupInterface    $setup
     * @param   string                  $tableName
     */
    protected function deleteTable(SchemaSetupInterface $setup, $tableName)
    {
        $setup->getConnection()->dropTable($setup->getTable($tableName));
    }
}