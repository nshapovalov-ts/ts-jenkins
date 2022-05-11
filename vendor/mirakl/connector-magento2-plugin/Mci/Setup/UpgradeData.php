<?php
namespace Mirakl\Mci\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Mirakl\Mci\Helper\Config as MciConfig;
use Mirakl\Mci\Helper\Data as MciHelper;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var MciConfig
     */
    private $mciConfig;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param   MciConfig       $mciConfig
     * @param   EavSetupFactory $eavSetupFactory
     */
    public function __construct(MciConfig $mciConfig, EavSetupFactory $eavSetupFactory)
    {
        $this->mciConfig = $mciConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $mapping = [
                MciConfig::XML_PATH_MCI_IMAGE_MAX_SIZE                 => 'mirakl_mci/images_import/image_max_size',
                MciConfig::XML_PATH_MCI_IMAGES_IMPORT_LIMIT            => 'mirakl_mci/images_import/limit',
                MciConfig::XML_PATH_MCI_IMAGES_IMPORT_HEADERS          => 'mirakl_mci/images_import/headers',
                MciConfig::XML_PATH_MCI_IMAGES_IMPORT_PROTOCOL_VERSION => 'mirakl_mci/images_import/protocol_version',
                MciConfig::XML_PATH_MCI_IMAGES_IMPORT_TIMEOUT          => 'mirakl_mci/images_import/timeout',
            ];

            foreach ($mapping as $newPath => $oldPath) {
                $oldValue = $this->mciConfig->getValue($oldPath);

                if ($oldValue !== null && $oldValue !== '') {
                    $this->mciConfig->setValue($newPath, $oldValue);
                }
            }
        }

        if (version_compare($context->getVersion(), '1.1.1', '<')) {
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
            $eavSetup->updateAttribute(Product::ENTITY, MciHelper::ATTRIBUTE_SHOPS_SKUS, 'frontend_input', 'textarea');
            $eavSetup->updateAttribute(Product::ENTITY, MciHelper::ATTRIBUTE_VARIANT_GROUP_CODES, 'frontend_input', 'textarea');
        }

        $setup->endSetup();
    }
}
