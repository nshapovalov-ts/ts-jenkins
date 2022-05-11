<?php
namespace Mirakl\FrontendDemo\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $connection = $setup->getConnection();
            $configTable = $setup->getTable('core_config_data');

            $oldKey = \Mirakl\FrontendDemo\Helper\Config::XML_PATH_AUTO_REMOVE_OFFERS;
            $newKey = \Mirakl\Connector\Helper\Config::XML_PATH_AUTO_REMOVE_OFFERS;

            $select = $connection->select()
                ->from($configTable, 'config_id')
                ->where('path = ?', $newKey)
                ->limit(1);

            if (false === $connection->fetchOne($select)) {
                $where = ['path = ?' => $oldKey];
                $bind = ['path' => $newKey];
                $connection->update($configTable, $bind, $where);
            }
        }

        $setup->endSetup();
    }
}
