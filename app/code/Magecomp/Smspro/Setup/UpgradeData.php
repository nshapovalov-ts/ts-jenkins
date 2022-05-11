<?php
namespace Magecomp\Smspro\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\State;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Customer\Model\Customer;

class UpgradeData implements UpgradeDataInterface
{
    private $customerSetupFactory;

    public function __construct(\Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
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
                'is_visible_in_grid' => true,
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
        if(version_compare($context->getVersion(), '1.3.0', '<')) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
                'ismobverify',
                [
                    'type' => 'int',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'label' => 'Is Mobile Verify',
                    'required' => false,
                    'visible' => true,
                    'system'=> 0,
                    'default' => 0,
                    'position' => 80
                ]
            );

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'ismobverify');
            $attribute->save();
        }
        $setup->endSetup();
    }
}