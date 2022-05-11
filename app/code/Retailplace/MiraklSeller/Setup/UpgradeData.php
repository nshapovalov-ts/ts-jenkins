<?php
/**
 * A Magento 2 module named Retailplace/MiraklSeller
 * Copyright (C) 2019
 *
 * This file included in Retailplace/MiraklSeller is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Retailplace\MiraklSeller\Setup;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{

    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), "1.0.1", "<")) {
            $eavSetup->removeAttribute(Product::ENTITY, 'min_order_amount');
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'min_order_amount',
                [
                    'group' => 'Mirakl Marketplace',
                    'type' => 'decimal',
                    'label' => 'Minimum order amount',
                    'input' => 'price',
                    'global' => 1,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => null,
                    'searchable' => true,
                    'filterable' => true,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => 'simple',
                    'note' => 'Selected shops minimum order is associated with the product. This field is automatically filled.',
                    'is_configurable' => false,
                ]
            );


        }
    }
}
