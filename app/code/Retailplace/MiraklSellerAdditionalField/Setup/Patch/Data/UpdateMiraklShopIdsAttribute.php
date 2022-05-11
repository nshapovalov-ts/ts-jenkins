<?php

/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateMiraklShopIdsAttribute
 */
class UpdateMiraklShopIdsAttribute implements DataPatchInterface
{
    /** @var string */
    const MIRAKL_SHOP_IDS = 'mirakl_shop_ids';

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * UpdateMiraklShopIdsAttribute constructor.
     *
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $logger
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->logger = $logger;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        try {
            $attribute = $this->attributeRepository->get(
                Product::ENTITY,
                self::MIRAKL_SHOP_IDS
            );
            $attribute->setIsFilterable(true);
            $attribute->setIsFilterableInSearch(true);
            $this->attributeRepository->save($attribute);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
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
}
