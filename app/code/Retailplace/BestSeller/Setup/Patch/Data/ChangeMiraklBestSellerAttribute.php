<?php

/**
 * Retailplace_BestSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\BestSeller\Setup\Patch\Data;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Retailplace\BestSeller\Api\Data\ProductBestSellerAttributesInterface;

/**
 * Class ChangeMiraklBestSellerAttribute
 */
class ChangeMiraklBestSellerAttribute implements DataPatchInterface
{
    /** @var string */
    public const MIRAKL_BEST_SELLER = 'mirakl_best_seller';

    /** @var \Magento\Eav\Api\AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * ChangeMiraklBestSellerAttribute constructor.
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
                ProductBestSellerAttributesInterface::BEST_SELLER
            );
            $attribute->setAttributeCode(self::MIRAKL_BEST_SELLER);
            $attribute->setDefaultFrontendLabel('Mirakl Best Seller');
            $attribute->setIsFilterable(false);
            $attribute->setIsFilterableInSearch(false);
            $attribute->setUsedInProductListing(false);
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
