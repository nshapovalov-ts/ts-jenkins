<?php

/**
 * Retailplace_Performance
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Performance\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Mirakl\Mci\Helper\Data as MciHelper;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ChangeNonProcessedMiraklImagesAttribute
 */
class ChangeNonProcessedMiraklImagesAttribute implements DataPatchInterface
{
    /** @var \Magento\Framework\Setup\ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var \Mirakl\Mci\Helper\Data */
    private $mciHelper;

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * ChangeNonProcessedMiraklImagesAttribute constructor
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     * @param \Mirakl\Mci\Helper\Data $mciHelper
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        MciHelper $mciHelper,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->mciHelper = $mciHelper;
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $imageAttributes = $this->mciHelper->getImagesAttributes();
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach ($imageAttributes as $imageAttribute) {
            $attributeCode = 'non_processed_' . $imageAttribute->getAttributeCode();
            $this->setGlobalScope($attributeCode);
        }
        $this->setGlobalScope('is_image_imported');

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
        return [
            CreateMiraklImageStatusAttributes::class,
            AddIsImportedProductAttributes::class
        ];
    }

    /**
     * Set scope "Global" to product attribute
     *
     * @param string $attributeCode
     * @return void
     */
    private function setGlobalScope($attributeCode)
    {
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                $attributeCode
            );
            $attribute->setIsGlobal(ScopedAttributeInterface::SCOPE_GLOBAL);
            $this->attributeRepository->save($attribute);
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
