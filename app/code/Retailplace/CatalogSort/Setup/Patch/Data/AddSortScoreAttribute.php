<?php

/**
 * Retailplace_CatalogSort
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CatalogSort\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Catalog\Model\Product;
use Retailplace\CatalogSort\Api\Data\ProductSortScoreAttributesInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AddSortScoreAttribute
 */
class AddSortScoreAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $eavSetup->addAttribute(
                Product::ENTITY,
                ProductSortScoreAttributesInterface::ATTRIBUTE_CODE,
                [
                    'type'                    => 'decimal',
                    'label'                   => ProductSortScoreAttributesInterface::ATTRIBUTE_DEFAULT_LABEL,
                    'input'                   => 'text',
                    'source'                  => '',
                    'frontend'                => '',
                    'required'                => false,
                    'backend'                 => '',
                    'global'                  => true,
                    'default'                 => 0,
                    'visible'                 => true,
                    'user_defined'            => true,
                    'filterable'              => false,
                    'comparable'              => false,
                    'visible_on_front'        => false,
                    'unique'                  => false,
                    'apply_to'                => 'simple,configurable,virtual,bundle,downloadable',
                    'group'                   => 'General',
                    'used_in_product_listing' => false,
                    'is_used_in_grid'         => true,
                    'is_visible_in_grid'      => false,
                    'is_filterable_in_grid'   => false,
                    'used_for_sort_by'        => false
                ]
            );

            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                ProductSortScoreAttributesInterface::ATTRIBUTE_CODE
            );
            $attribute->setData('mirakl_is_exportable', false);
            $this->attributeRepository->save($attribute);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     *
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->removeAttribute(Product::ENTITY, ProductSortScoreAttributesInterface::ATTRIBUTE_CODE);

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
}
