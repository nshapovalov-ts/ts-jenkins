<?php

/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mirakl\Mci\Helper\Data as MciHelper;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Catalog\Model\Config as CatalogConfig;
use Magento\Eav\Api\AttributeManagementInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CreateMiraklImageStatusAttributes
 */
class CreateMiraklImageStatusAttributes implements DataPatchInterface
{
    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Magento\Eav\Setup\EavSetupFactory */
    private $eavSetupFactory;

    /** @var \Mirakl\Mci\Helper\Data */
    private $mciHelper;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Magento\Catalog\Model\Config */
    private $catalogConfig;

    /** @var \Magento\Eav\Api\AttributeManagementInterface */
    private $attributeManagement;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * CreateMiraklImageStatusAttributes constructor
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Eav\Api\AttributeManagementInterface $attributeManagement
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        MciHelper $mciHelper,
        AttributeRepositoryInterface $attributeRepository,
        CatalogConfig $catalogConfig,
        AttributeManagementInterface $attributeManagement,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->mciHelper = $mciHelper;
        $this->attributeRepository = $attributeRepository;
        $this->catalogConfig = $catalogConfig;
        $this->attributeManagement = $attributeManagement;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $imageAttributes = $this->mciHelper->getImagesAttributes();
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach ($imageAttributes as $imageAttribute) {
            $attributeCode = 'non_processed_' . $imageAttribute->getAttributeCode();
            $eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                [
                    'type' => 'int',
                    'label' => 'non_processed_' . $imageAttribute->getDefaultFrontendLabel(),
                    'input' => 'boolean',
                    'source' => '',
                    'frontend' => '',
                    'required' => false,
                    'backend' => '',
                    'sort_order' => '30',
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'default' => null,
                    'visible' => true,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'unique' => false,
                    'apply_to' => '',
                    'group' => 'General',
                    'used_in_product_listing' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'option' => ['values' => [""]]
                ]
            );

            try {
                $attribute = $this->attributeRepository->get(
                    Product::ENTITY,
                    $attributeCode
                );
                $attribute->setData('mirakl_is_exportable', false);
                $this->attributeRepository->save($attribute);
                $attributeList[] = $attributeCode;
            } catch (Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }
        $this->assignAttributesToSets($attributeList, $eavSetup);

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
        return [];
    }

    /**
     * @param string[] $attributeList
     * @param $eavSetup
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function assignAttributesToSets($attributeList, $eavSetup)
    {
        foreach ($attributeList as $key => $attributeCode) {
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
            $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
            foreach ($attributeSetIds as $attributeSetId) {
                if ($attributeSetId) {
                    $group_id = $this->catalogConfig->getAttributeGroupId($attributeSetId, 'Mirakl Root Attributes');
                    if (empty($group_id)) {
                        continue;
                    }

                    $this->attributeManagement->assign(
                        'catalog_product',
                        $attributeSetId,
                        $group_id,
                        $attributeCode,
                        500 + ($key * 100)
                    );
                }
            }
        }
    }

}
