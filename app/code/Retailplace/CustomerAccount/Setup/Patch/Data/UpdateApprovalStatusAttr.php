<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateApprovalStatusAttr implements DataPatchInterface
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
     * UpdateBundleRelatedEntityTypes constructor.
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

    public static function getDependencies()
    {
        return [
            CategoriesOfInterestAttributes::class
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->updateAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'is_approved',
            'source_model',
            \Retailplace\CustomerAccount\Model\Config\Source\AttributeOptions::class
        );

        $eavSetup->updateAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'is_approved',
            'default_value',
            'pending'
        );

        $eavSetup->updateAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'is_auto_approved_status',
            'backend_type',
            'varchar'
        );

        $eavSetup->updateAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'is_auto_approved_status',
            'is_user_defined',
            0
        );
    }
}
