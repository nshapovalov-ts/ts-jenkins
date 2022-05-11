<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UploadFileAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * IncompleteAppGroup constructor.
     * @param AttributeSetFactory $attributeSetFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        AttributeSetFactory $attributeSetFactory,
        CustomerSetupFactory $customerSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->attributeSetFactory = $attributeSetFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        /** @var CustomerSetup $eavSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, 'upload_file', [
            'input' => 'file',
            'type' => 'text',
            'label' => 'Upload file',
            'required' => false,
            'source' => '',
            'visible' => true,
            'user_defined' => true,
            'is_used_in_grid'    => true,
            'is_visible_in_grid' => true,
            'sort_order' => 400,
            'position'  => 10000,
            'system' => false,
            'backend' => ''
        ]);

        $fileUploading = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'upload_file')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer', 'customer_account_edit'],
            ]);
        $fileUploading->save();
    }
}
