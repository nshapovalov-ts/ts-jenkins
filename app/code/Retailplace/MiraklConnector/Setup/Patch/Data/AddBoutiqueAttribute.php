<?php
/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

/**
 * Class AddBoutiqueAttribute
 */
class AddBoutiqueAttribute implements DataPatchInterface
{
    /**
     * Attribute code
     */
    const BOUTIQUE = 'boutique';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::BOUTIQUE,
            [
                'group'                   => 'General',
                'frontend'                => '',
                'input'                   => 'boolean',
                'type'                    => 'int',
                'label'                   => 'Boutique',
                'global'                  => true,
                'source'                  => Boolean::class,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => 0,
                'searchable'              => false,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'filterable_in_search'    => true,
                'unique'                  => false,
                'apply_to'                => 'simple,configurable,virtual,bundle,downloadable',
                'note'                    => 'Boutique - all the offers that “product on differentiator includes boutique”',
                'is_configurable'         => false,
                'mirakl_is_exportable'    => false
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, self::BOUTIQUE);

        $this->moduleDataSetup->getConnection()->endSetup();
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
}
