<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateDataModelForAttributes implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateDataModelForAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public static function getDependencies()
    {
        return [
            AnnualPurchasingSpendAttr::class,
            CategoriesOfInterestAttributes::class,
            FrequentlyOrderAttribute::class,
            PurchasePrioritiesAttribute::class
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $attrList = [
            'annual_purchasing_spend',
            'categories_usually_buy',
            'my_network',
            'frequently_order',
            'purchase_priorities',
        ];

        $eavTable = $this->moduleDataSetup->getTable('eav_attribute');
        $customerEavTable = $this->moduleDataSetup->getTable('customer_eav_attribute');
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()->from($eavTable, ['attribute_id', 'frontend_input'])->where('attribute_code IN(?)', $attrList);
        $attributes = $connection->fetchAll($select);
        $data = [];
        foreach ($attributes as $attribute) {
            $dataModel = 'Amasty\CustomerAttributes\Model\Eav\Attribute\Data\\' . ucfirst($attribute['frontend_input']);
            $data[] = [
                'attribute_id' => $attribute['attribute_id'],
                'data_model' => $dataModel
            ];
        }
        $connection->insertOnDuplicate($customerEavTable, $data, ['attribute_id', 'data_model']);

        $this->moduleDataSetup->endSetup();
    }
}
