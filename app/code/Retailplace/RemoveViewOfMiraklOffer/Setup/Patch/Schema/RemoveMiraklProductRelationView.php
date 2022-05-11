<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\RemoveViewOfMiraklOffer\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Remove MySQL View mirakl_offer_simple_product  and mirakl_offer_configurable_product.
 */
class RemoveMiraklProductRelationView implements SchemaPatchInterface
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
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $this->schemaSetup->getConnection()->query("DROP VIEW IF EXISTS {$this->schemaSetup->getTable('mirakl_offer_simple_product')}");
        $this->schemaSetup->getConnection()->query("DROP VIEW IF EXISTS {$this->schemaSetup->getTable('mirakl_offer_configurable_product')}");
        $this->schemaSetup->endSetup();
        return $this;
    }
}
