<?php
namespace Mirakl\Mcm\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Mirakl\Mcm\Helper\Data as McmHelper;

class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param   EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->removeAttribute(Product::ENTITY, McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID);
        $eavSetup->addAttribute(
            Product::ENTITY,
            McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID,
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'varchar',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Mirakl MCM Product Id',
                'note'                    => 'Contains Mirakl product id created by MCM. This field is automatically filled.',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'used_in_product_listing' => true,
                'mirakl_is_exportable'    => false,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER);
        $eavSetup->addAttribute(
            Product::ENTITY,
            McmHelper::ATTRIBUTE_MIRAKL_IS_OPERATOR_MASTER,
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Is Operator Master',
                'note'                    => 'Is the operator master of this product? This field is automatically filled.',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'simple',
                'is_configurable'         => false,
                'default'                 => 1,
                'used_in_product_listing' => true,
                'mirakl_is_exportable'    => false,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, McmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE);
        $eavSetup->addAttribute(
            Product::ENTITY,
            McmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE,
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'MCM Variant Group Code',
                'note'                    => 'Contains variant group code from MCM import. This field is automatically filled.',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'unique'                  => false,
                'apply_to'                => 'configurable',
                'is_configurable'         => false,
                'used_in_product_listing' => false,
                'mirakl_is_exportable'    => false,
            ]
        );
    }
}
