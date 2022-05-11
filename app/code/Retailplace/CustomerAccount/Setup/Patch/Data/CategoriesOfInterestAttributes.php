<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CategoriesOfInterestAttributes implements DataPatchInterface
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

        $customerSetup->addAttribute(Customer::ENTITY, 'categories_usually_buy', [
            'input' => 'multiselectimg',
            'type' => 'varchar',
            'label' => 'What are the categories you usually buy?',
            'required' => true,
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
            'visible' => true,
            'user_defined' => true,
            'is_used_in_grid'    => true,
            'is_visible_in_grid' => true,
            'sort_order' => 215,
            'position'  => 1009,
            'system' => false,
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend'
        ]);

        $categoriesAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'categories_usually_buy')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer'],
            ]);
        $categoriesAttribute->save();

        $customerSetup->addAttribute(Customer::ENTITY, 'my_network', [
            'input' => 'multiselectimg',
            'type' => 'varchar',
            'label' => 'My network, please share memeberships and subscriptions',
            'required' => true,
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
            'visible' => true,
            'user_defined' => true,
            'is_used_in_grid'    => true,
            'is_visible_in_grid' => true,
            'sort_order' => 230,
            'position'  => 1010,
            'system' => false,
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend'
        ]);

        $myNetworkAttribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'my_network')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer'],
            ]);
        $myNetworkAttribute->save();
    }
}
