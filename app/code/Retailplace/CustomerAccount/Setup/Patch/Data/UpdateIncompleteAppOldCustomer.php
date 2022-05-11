<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Retailplace\CustomerAccount\Model\Config\Source\IncompleteApplicationStatus;

class UpdateIncompleteAppOldCustomer implements DataPatchInterface
{
    /**
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateIncompleteAppOldCustomer constructor.
     * @param Attribute $eavAttribute
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        Attribute $eavAttribute,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->eavAttribute = $eavAttribute;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $customerEntityTable = $connection->getTableName('customer_entity');
        $customerEntityTextTable = $connection->getTableName('customer_entity_text');
        $customerEntityIntTable = $connection->getTableName('customer_entity_int');
        $isApprovedId = $this->eavAttribute->getIdByCode('customer', 'is_approved');
        $incompleteApplicationId = $this->eavAttribute->getIdByCode('customer', 'incomplete_application');
        $data = [];

        //Getting customer approval status
        $select = $connection->select()
            ->from(
                ['e' => $customerEntityTable],
                ['entity_id']
            )->joinLeft(
                ['cet' => $customerEntityTextTable],
                "cet.entity_id = e.entity_id"
            )->where(
                'cet.attribute_id = ?',
                $isApprovedId
            )->where(
                'cet.value IN(?)',
                ['approved', 'notapproved']
            );

        $approvalData = $connection->fetchAll($select);
        foreach ($approvalData as $row) {
            $data[] = [
                'attribute_id' => (int) $incompleteApplicationId,
                'entity_id'    => (int) $row['entity_id'],
                'value'        => IncompleteApplicationStatus::COMPLETE_APPLICATION
            ];

            if (sizeof($data) >= 1000) {
                $connection->insertOnDuplicate($customerEntityIntTable, $data, ['attribute_id', 'entity_id', 'value']);
                $data = [];
            }
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($customerEntityIntTable, $data, ['attribute_id', 'entity_id', 'value']);
        }

        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies()
    {
        return [
            IncompleteApplicationAttribute::class
        ];
    }

    public function getAliases()
    {
        return [];
    }
}
