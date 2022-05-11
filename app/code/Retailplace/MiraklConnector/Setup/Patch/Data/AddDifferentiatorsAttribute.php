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
 * Class AddDifferentiatorsAttribute
 */
class AddDifferentiatorsAttribute implements DataPatchInterface
{
    /**
     *  Differentiators option values of mirakl_seller
     */
    const DIFFERENTIATORS_MAPPING = [
        'Eco-friendly'        => 'Eco-friendly',
        'Homemade'            => 'Homemade',
        'Woman-owned'         => 'Woman-owned',
        'Organic'             => 'Organic',
        'Social Good'         => 'Social-good'
    ];

    /**
     *  Attribute code
     */
    const DIFFERENTIATORS = 'differentiators';

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
            self::DIFFERENTIATORS,
            [
                'group'                   => 'General',
                'type'                    => 'varchar',
                'backend'                 => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'frontend'                => '',
                'label'                   => 'Differentiators',
                'note'                    => 'Differentiators - all the offers that have differentiators for their respective sellers',
                'input'                   => 'multiselect',
                'source'                  => '',
                'class'                   => '',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'default'                 => null,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple,configurable,virtual,bundle,downloadable',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'filterable_in_search'    => true,
                'mirakl_is_exportable'    => false,
                'option'                  => [
                    'values' => array_keys(self::DIFFERENTIATORS_MAPPING)
                ]
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * To remove attribute
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, self::DIFFERENTIATORS);

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
