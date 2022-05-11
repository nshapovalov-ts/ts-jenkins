<?php
namespace Magecomp\Smspro\Setup;
 
use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
 
class InstallData implements InstallDataInterface
{
    private $customerSetupFactory;

    public function __construct(\Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory)
    {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
 
        $customerSetup->addAttribute(
            Customer::ENTITY,
            'mobilenumber',
            [
                'type' => 'text',
                'label' => 'Mobile Number',
                'frontend_input' => 'text',
                'required' => false,
                'visible' => true,
                'system'=> 0,
                'position' => 80,
                'is_used_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'mobilenumber');
		
		$used_in_forms[]="adminhtml_customer";
		$used_in_forms[]="checkout_register";
		$used_in_forms[]="customer_account_create";
		$used_in_forms[]="customer_account_edit";
		$used_in_forms[]="adminhtml_checkout";
		
        $attribute->setData('used_in_forms', $used_in_forms);
        $attribute->save();

        $setup->endSetup();
    }
}