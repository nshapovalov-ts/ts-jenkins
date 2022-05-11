<?php

/**
 * Retailplace_BestSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\BestSeller\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Retailplace\BestSeller\Model\BestSellersCalculatedManagement;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

/**
 * Class TransferBestSellerData
 */
class TransferBestSellerData implements DataPatchInterface
{
    /** @var \Retailplace\BestSeller\Model\BestSellersCalculatedManagement */
    private $bestSellerManagement;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /**
     * TransferBestSellerData constructor.
     *
     * @param \Retailplace\BestSeller\Model\BestSellersCalculatedManagement $bestSellerManagement
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        BestSellersCalculatedManagement $bestSellerManagement,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->bestSellerManagement = $bestSellerManagement;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Apply Patch
     */
    public function apply()
    {
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter(
            ChangeMiraklBestSellerAttribute::MIRAKL_BEST_SELLER,
            ['eq' => 1]
        );
        $productIds = $productCollection->getColumnValues('entity_id');
        $this->bestSellerManagement->updateAttributeValues($productIds);
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
        return [BestSellerAttribute::class];
    }
}
