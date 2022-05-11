<?php

/**
 * Retailplace_SellerTags
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerTags\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use Retailplace\SellerTags\Api\Data\SellerTagsAttributes;

/**
 * Class AddClosedUntilAttribute
 */
class AddClosedUntilAttribute implements DataPatchInterface
{
    /** @var \Magento\Eav\Setup\EavSetupFactory */
    private $eavSetupFactory;

    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * AddClosedUntilAttribute constructor.
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
     * Apply patch
     */
    public function apply()
    {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        try {
            $eavSetup->addAttribute(
                Product::ENTITY,
                SellerTagsAttributes::PRODUCT_CLOSED_TO,
                [
                    'group' => 'General',
                    'type' => 'varchar',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Closed until',
                    'input' => 'date',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'filterable_in_search' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => 'simple,configurable,virtual,bundle,downloadable'
                ]
            );

            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                SellerTagsAttributes::PRODUCT_CLOSED_TO
            );
            $attribute->setData('mirakl_is_exportable', false);
            $this->attributeRepository->save($attribute);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
