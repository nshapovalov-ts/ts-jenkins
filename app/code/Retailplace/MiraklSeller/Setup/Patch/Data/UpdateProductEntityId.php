<?php declare(strict_types=1);

namespace Retailplace\MiraklSeller\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateProductEntityId implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateEntityId constructor.
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
        $miraklOfferTable = $this->moduleDataSetup->getTable('mirakl_offer');

        $select = $this->moduleDataSetup->getConnection()->select()->from(
            ['mo' => $miraklOfferTable],
            ['mo.offer_id', 'mo.product_sku']
        );

        $catalogProductTable = $this->moduleDataSetup->getTable('catalog_product_entity');

        $select->joinLeft(
            ['e' => $catalogProductTable],
            'mo.product_sku = e.sku',
            ['e.entity_id']
        );

        $data = $this->moduleDataSetup->getConnection()->fetchAll($select);
        foreach ($data as $row) {
            $bind = ['entity_id' => $row['entity_id']];
            $where = ['offer_id = ?' => (int)$row['offer_id']];
            $this->moduleDataSetup->getConnection()->update($miraklOfferTable, $bind, $where);
        }

    }
}
