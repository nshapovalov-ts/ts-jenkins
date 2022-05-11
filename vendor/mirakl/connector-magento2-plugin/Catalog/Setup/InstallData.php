<?php
namespace Mirakl\Catalog\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
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

        $eavSetup->removeAttribute(Product::ENTITY, 'mirakl_sync');
        $eavSetup->addAttribute(
            Product::ENTITY,
            'mirakl_sync',
            [
                'group'                     => 'Mirakl Marketplace',
                'type'                      => 'int',
                'backend'                   => '',
                'frontend'                  => '',
                'label'                     => 'Synchronize',
                'note'                      => 'If enabled, product will be synchronized on the Mirakl platform automatically.',
                'input'                     => 'boolean',
                'class'                     => '',
                'source'                    => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'                    => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                   => true,
                'required'                  => false,
                'user_defined'              => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'unique'                    => false,
                'apply_to'                  => 'simple',
                'is_configurable'           => false,
                'default'                   => 0,
                'used_in_product_listing'   => true,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, 'mirakl_category_id');
        $eavSetup->addAttribute(
            Product::ENTITY,
            'mirakl_category_id',
            [
                'group'                     => 'Mirakl Marketplace',
                'type'                      => 'int',
                'backend'                   => '',
                'frontend'                  => '',
                'label'                     => 'Category',
                'note'                      => 'This is the category associated with the product. This category will be sent during synchronization with Mirakl platform.',
                'input'                     => 'select',
                'class'                     => '',
                'source'                    => 'Mirakl\Catalog\Eav\Model\Product\Attribute\Source\Category',
                'global'                    => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                   => true,
                'required'                  => false,
                'user_defined'              => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'unique'                    => false,
                'apply_to'                  => 'simple',
                'is_configurable'           => false,
                'used_in_product_listing'   => false,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, 'mirakl_authorized_shop_ids');
        $eavSetup->addAttribute(
            Product::ENTITY,
            'mirakl_authorized_shop_ids',
            [
                'group'                     => 'Mirakl Marketplace',
                'type'                      => 'text',
                'backend'                   => 'Mirakl\Catalog\Model\Product\Attribute\Backend\Shop\Authorized',
                'frontend'                  => '',
                'label'                     => 'Authorized Shops',
                'note'                      => 'Only selected shops will be allowed to add offers on the product. Leave empty to authorize all shops.',
                'input'                     => 'multiselect',
                'class'                     => '',
                'source'                    => 'Mirakl\Connector\Eav\Model\Entity\Attribute\Source\Shop',
                'global'                    => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                   => true,
                'required'                  => false,
                'user_defined'              => true,
                'searchable'                => false,
                'filterable'                => false,
                'comparable'                => false,
                'visible_on_front'          => false,
                'unique'                    => false,
                'apply_to'                  => 'simple',
                'is_configurable'           => false,
                'used_in_product_listing'   => false,
            ]
        );

        $eavSetup->removeAttribute(Category::ENTITY, 'mirakl_sync');
        $eavSetup->addAttribute(
            Category::ENTITY,
            'mirakl_sync',
            [
                'group'             => 'Mirakl Marketplace',
                'type'              => 'int',
                'backend'           => '',
                'frontend'          => '',
                'label'             => 'Synchronize',
                'note'              => 'If enabled, this category will be automatically synchronized on the Mirakl platform.',
                'input'             => 'boolean',
                'input_renderer'    => '',
                'class'             => '',
                'source'            => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'            => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'           => true,
                'required'          => false,
                'user_defined'      => true,
                'default'           => 0,
                'visible_on_front'  => false,
                'unique'            => false,
            ]
        );
    }
}
