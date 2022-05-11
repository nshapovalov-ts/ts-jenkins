<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklPromotion\Api\Data\ProductAttributesInterface;

/**
 * Class SellerSpecialsAttribute
 */
class SellerSpecialsAttribute implements DataPatchInterface
{
    /** @var array */
    public const ATTRIBUTES_DATA = [
        ProductAttributesInterface::SELLER_SPECIALS => ['label' => 'Seller Specials']
    ];

    /** @var \Magento\Eav\Setup\EavSetupFactory */
    private $eavSetupFactory;

    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * ExclusiveAttributes constructor.
     *
     * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        $this->addProductAttributes();
    }

    /**
     * Get Patch Aliases
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Get Patch Dependencies
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Add Product Attributes
     */
    private function addProductAttributes()
    {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (self::ATTRIBUTES_DATA as $attributeCode => $attributeData) {
            try {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    $attributeCode,
                    [
                        'group' => 'General',
                        'type' => 'int',
                        'backend' => '',
                        'frontend' => '',
                        'label' => $attributeData['label'],
                        'input' => 'boolean',
                        'source' => Boolean::class,
                        'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => true,
                        'default' => 0,
                        'searchable' => false,
                        'filterable' => true,
                        'filterable_in_search' => true,
                        'comparable' => false,
                        'visible_on_front' => true,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'position' => 25,
                        'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
                    ]
                );

                $attribute = $this->attributeRepository->get(
                    Product::ENTITY,
                    $attributeCode
                );
                $attribute->setData('mirakl_is_exportable', false);
                $this->attributeRepository->save($attribute);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
