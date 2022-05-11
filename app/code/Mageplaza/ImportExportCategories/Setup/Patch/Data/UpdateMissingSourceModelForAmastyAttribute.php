<?php

/**
 * Mageplaza_ImportExportCategories
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Mageplaza\ImportExportCategories\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Category;
use Amasty\HidePrice\Model\Source\Group;
use Amasty\HidePrice\Model\Source\PriceMode;

/**
 * Class UpdateMissingSourceModelForAmastyAttribute
 */
class UpdateMissingSourceModelForAmastyAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
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
        $attribute = $this->eavConfig->getAttribute(Category::ENTITY, 'am_hide_price_mode_cat')
            ->addData(['source_model' => PriceMode::class]);
        $attribute->save();
        $attribute = $this->eavConfig->getAttribute(Category::ENTITY, 'am_hide_price_customer_gr_cat')
            ->addData(['source_model' => Group::class]);
        $attribute->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
