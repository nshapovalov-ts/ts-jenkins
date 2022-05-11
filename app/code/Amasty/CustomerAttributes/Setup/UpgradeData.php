<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;

class UpgradeData implements UpgradeDataInterface
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
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($context->getVersion() && version_compare($context->getVersion(), '1.1.1', '<')) {
            $this->removeUnusedAttribute();
        }

        $setup->endSetup();
    }

    /**
     * in version 1.0.0 was added attribute which now is not needed any more
     */
    private function removeUnusedAttribute()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'am_is_activated');
    }
}
