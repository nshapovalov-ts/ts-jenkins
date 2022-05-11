<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class IndustryData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * IndustryData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
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
        $this->moduleDataSetup->getConnection()->startSetup();
        $industryTable = $this->moduleDataSetup->getTable('mirakl_additionalfield_industryexclusions');
        $data = [
            ["airbnb", "Airbnb"],
            ["motel", "Motel"],
            ["hotel", "Hotel"],
            ["accommodation-group", "Accommodation Group"],
            ["holiday-park", "Holiday park"],
            ["backpacker", "Backpacker"],
            ["association", "Association"],
            ["events", "Events"],
            ["service-provider", "Service provider"],
            ["accounting-and-finance", "Accounting and Finance"],
            ["other", "Other"]
        ];

        $this->moduleDataSetup->getConnection()->insertArray($industryTable, ['code', 'label'], $data);
        $this->moduleDataSetup->endSetup();
    }
}
