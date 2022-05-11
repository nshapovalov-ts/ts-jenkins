<?php

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Setup\Patch\Schema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddIndexes implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
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
        $this->schemaSetup->startSetup();
        $this->schemaSetup->getConnection()->addIndex(
            'mirakl_offer',
            $this->schemaSetup->getIdxName('mirakl_offer', ['entity_id']),
            ['entity_id']
        );

        $this->schemaSetup->getConnection()->addIndex(
            'mirakl_offer',
            $this->schemaSetup->getIdxName('mirakl_offer', ['shop_id', 'active']),
            ['shop_id', 'active']
        );
        $this->schemaSetup->endSetup();
    }
}
