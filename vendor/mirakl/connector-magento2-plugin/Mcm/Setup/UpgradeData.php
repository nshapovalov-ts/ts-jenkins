<?php
namespace Mirakl\Mcm\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Mirakl\Mcm\Helper\Data as McmHelper;

class UpgradeData implements UpgradeDataInterface
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
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->updateAttribute(Product::ENTITY, McmHelper::ATTRIBUTE_MIRAKL_PRODUCT_ID, 'backend_type', 'varchar');
            $eavSetup->updateAttribute(Product::ENTITY, McmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE, 'backend_type', 'varchar');
        }

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->updateAttribute(Product::ENTITY, McmHelper::ATTRIBUTE_MIRAKL_VARIANT_GROUP_CODE, 'apply_to', 'simple,configurable');
        }

        $setup->endSetup();
    }
}
