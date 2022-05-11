<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddIndustryBusinessUseCustomerAttribute implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var CustomerSetup
     */
    private $customerSetupFactory;
    /**
     * @var SetFactory
     */
    private $attributeSetFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param SetFactory $attributeSetFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        SetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if($attributeId = $customerSetup->getAttributeId(Customer::ENTITY, 'industry_business_use')) {
            
            $customerSetup->updateAttribute(Customer::ENTITY,'industry_business_use','source_model','Retailplace\MiraklSellerAdditionalField\Model\Option\IndustryBusinessUseOptionsProvider'); 

            //Create the attribute
        }else{
            $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
            $attributeSetId = $customerEntity->getDefaultAttributeSetId();
            
            /** @var $attributeSet Set w*/
            $attributeSet = $this->attributeSetFactory->create();
            $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
            
            $customerSetup->addAttribute(
                Customer::ENTITY,
                'industry_business_use',
                [
                    'label' => 'industry_business_use',
                    'input' => 'multiselect',
                    'type' => 'varchar',
                    'source' => 'Retailplace\MiraklSellerAdditionalField\Model\Option\IndustryBusinessUseOptionsProvider',
                    'required' => true,
                    'position' => 333,
                    'visible' => true,
                    'system' => false,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => true,
                    'is_filterable_in_grid' => true,
                    'is_searchable_in_grid' => false,
                    'backend' => ''
                ]
            );
            
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'industry_business_use');
            $attribute->addData([
                'used_in_forms' => [
                    'adminhtml_customer',
                    'adminhtml_checkout',
                    'customer_account_create',
                    'customer_account_edit'
                ]
            ]);
            $attribute->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId
            
            ]);
            $attribute->save();
        }

        

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'industry_business_use');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
        
        ];
    }
}

