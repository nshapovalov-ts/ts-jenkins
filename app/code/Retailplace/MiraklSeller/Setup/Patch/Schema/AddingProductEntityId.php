<?php declare(strict_types=1);

namespace Retailplace\MiraklSeller\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddingProductEntityId implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }


    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->moduleDataSetup->getConnection()->addColumn(
            $this->moduleDataSetup->getTable('mirakl_offer'),
            'entity_id',
            [
                'type'     => Table::TYPE_INTEGER,
                'nullable' => false,
                'default' => 0,
                'comment'  => 'Product Entity Id',
            ]
        );
        $this->moduleDataSetup->endSetup();
    }
}
