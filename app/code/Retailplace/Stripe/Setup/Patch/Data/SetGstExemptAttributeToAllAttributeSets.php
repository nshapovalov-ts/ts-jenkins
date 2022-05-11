<?php

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Setup\Patch\Data;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Catalog\Model\Product;

/**
 * Class SetGstExemptAttributeToAllAttributeSets
 */
class SetGstExemptAttributeToAllAttributeSets implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory|EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AttributeManagementInterface
     */
    private $attributeManagement;

    /**
     * AutoAcceptStatus constructor.
     * @param AttributeManagementInterface $attributeManagement
     * @param Config $config
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        AttributeManagementInterface $attributeManagement,
        Config $config,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->config = $config;
        $this->attributeManagement = $attributeManagement;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
        foreach ($attributeSetIds as $attributeSetId) {
            if ($attributeSetId) {
                $group_id = $this->config->getAttributeGroupId($attributeSetId, 'Mirakl Root Attributes');
                if (empty($group_id)) {
                    continue;
                }

                $this->attributeManagement->assign(
                    'catalog_product',
                    $attributeSetId,
                    $group_id,
                    'gst_exempt',
                    500
                );
            }
        }
    }
}
