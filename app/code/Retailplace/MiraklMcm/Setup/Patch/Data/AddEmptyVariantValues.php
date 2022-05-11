<?php

namespace Retailplace\MiraklMcm\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mirakl\Mci\Helper\Data as MciHelper;

class AddEmptyVariantValues implements DataPatchInterface
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
     * @var MciHelper
     */
    protected $mciHelper;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param MciHelper $mciHelper
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        MciHelper $mciHelper
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->mciHelper = $mciHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        foreach ($this->mciHelper->getVariantAttributes() as $attribute) {
            if (in_array(
                $attribute->getFrontendInput(),
                ['select', 'multiselect']
            )) {
                $options = [
                    'values' => [
                        \Retailplace\MiraklMcm\Helper\Data::EMPTY_VALUE_PLACEHOLDER
                    ],
                    'attribute_id' => $attribute->getId(),
                ];
                $eavSetup->addAttributeOption($options);
            }
        }
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
