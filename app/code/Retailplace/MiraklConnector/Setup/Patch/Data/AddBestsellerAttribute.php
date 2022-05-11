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
 * Class AddBestsellerAttribute
 */
class AddBestsellerAttribute implements DataPatchInterface
{
    /**
     * Attribute code
     */
    const BEST_SELLER = 'best_seller';

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
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, self::BEST_SELLER);
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::BEST_SELLER,
            [
                'group'                   => 'General',
                'frontend'                => '',
                'input'                   => 'boolean',
                'type'                    => 'int',
                'label'                   => 'Best Seller',
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
                'note'                    => 'Best Seller - all the mirakl product that have “Top product with Best Seller option”',
                'is_configurable'         => false,
                'mirakl_is_exportable'    => false
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * removes the attribute
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, self::BEST_SELLER);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
