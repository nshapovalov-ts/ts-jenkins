<?php
namespace Mirakl\Mci\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Category;
use Mirakl\Mci\Helper\Data as MciHelper;
use Mirakl\Mci\Helper\Hash;

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
     * @param EavSetupFactory $eavSetupFactory
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

        $eavSetup->removeAttribute(Category::ENTITY, MciHelper::ATTRIBUTE_ATTR_SET);
        $eavSetup->addAttribute(
            Category::ENTITY,
            MciHelper::ATTRIBUTE_ATTR_SET,
            [
                'group'            => 'Mirakl Marketplace',
                'type'             => 'int',
                'backend'          => '',
                'frontend'         => '',
                'label'            => 'Attribute Set',
                'note'             => 'Associate an attribute set to this category. It will be used to synchronize product attributes with MCI.',
                'input'            => 'select',
                'input_renderer'   => '',
                'class'            => '',
                'source'           => 'Mirakl\Mci\Eav\Model\Entity\Attribute\Source\AttributeSet',
                'global'           => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'          => true,
                'required'         => false,
                'user_defined'     => true,
                'default'          => 0,
                'visible_on_front' => false,
                'unique'           => false,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, MciHelper::ATTRIBUTE_SHOPS_SKUS);
        $eavSetup->addAttribute(
            Product::ENTITY,
            MciHelper::ATTRIBUTE_SHOPS_SKUS,
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Shops SKUs',
                'note'                    => 'Contains all shops SKUs as shop_id1|sku1,shop_id2|sku2... This field is automatically filled.',
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
                'used_in_product_listing' => false,
            ]
        );

        $eavSetup->removeAttribute(Product::ENTITY, MciHelper::ATTRIBUTE_VARIANT_GROUP_CODES);
        $eavSetup->addAttribute(
            Product::ENTITY,
            MciHelper::ATTRIBUTE_VARIANT_GROUP_CODES,
            [
                'group'                   => 'Mirakl Marketplace',
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Variant Group Codes',
                'note'                    => 'Contains all shops variant group codes as shop_id1|variant_id1,shop_id2|variant_id2... This field is automatically filled.',
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
            ]
        );

        // Create table 'mirakl_product_import'
        $setup->getConnection()->dropTable($setup->getTable(Hash::TABLE_NAME));
        $table = $setup->getConnection()
            ->newTable($setup->getTable(Hash::TABLE_NAME))
            ->addColumn('shop_id', Table::TYPE_INTEGER, null, [
                'primary' => true, 'unsigned' => true, 'nullable' => false, 'default' => '0'
            ], 'Shop Id')
            ->addColumn('sku', Table::TYPE_TEXT, 64, ['primary' => true, 'nullable' => false, 'default' => ''], 'SKU')
            ->addColumn('hash', Table::TYPE_TEXT, 40, ['nullable' => false, 'default' => ''], 'Hash')
            ->setComment('Mirakl Product Import');

        $setup->getConnection()->createTable($table);

        // Add some custom columns to 'catalog_eav_attribute' table
        $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
            'mirakl_is_variant',
            [
                'type'     => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default'  => '0',
                'comment'  => 'Is Mirakl Variant',
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
            'mirakl_is_exportable',
            [
                'type'     => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default'  => '1',
                'comment'  => 'Is Mirakl Exportable',
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('catalog_eav_attribute'),
            'mirakl_is_localizable',
            [
                'type'     => Table::TYPE_SMALLINT,
                'unsigned' => true,
                'nullable' => false,
                'default'  => '0',
                'comment'  => 'Is Mirakl Localizable',
            ]
        );

        // Disable Mirakl Exportable status for system attribute because they can not be modified in Magento (bug)
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('eav_attribute'))
            ->columns('attribute_id')
            ->where('frontend_input = ?', 'select')
            ->where('is_user_defined = ?', 0);

        $attributeIds = $setup->getConnection()->fetchCol($select);

        if (!empty($attributeIds)) {
            $setup->getConnection()->update(
                $setup->getTable('catalog_eav_attribute'),
                ['mirakl_is_exportable' => 0],
                ['attribute_id IN (?)' => $attributeIds]
            );
        }
    }
}
