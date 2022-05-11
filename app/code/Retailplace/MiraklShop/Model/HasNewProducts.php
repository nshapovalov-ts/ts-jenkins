<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Mirakl\MMP\FrontOperator\Domain\ShopFactory;
use Amasty\Shopby\Model\Layer\Filter\IsNew\Helper;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Retailplace\MiraklShop\Api\ShopRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Retailplace\MiraklSellerAdditionalField\Setup\Patch\Data\UpdateMiraklShopIdsAttribute;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class HasNewProducts
 * Shop updater
 */
class HasNewProducts
{
    /** @var int */
    private const COLLECTION_PAGE_SIZE = 1000;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Helper
     */
    private $isNewHelper;

    /**
     * @var ShopRepositoryInterface
     */
    private $shopRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $shopsHasNewProducts = [];

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ShopRepositoryInterface $shopRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Helper $isNewHelper
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ShopRepositoryInterface $shopRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Helper $isNewHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->isNewHelper = $isNewHelper;
        $this->shopRepository = $shopRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Collect shops array which have new products and are not marked as has_new_products now
     *
     * @return ShopInterface[]|array
     */
    public function getShopsWithNewProducts(): array
    {
        $this->collectNewProducts();
        if (empty($this->shopsHasNewProducts)) {
            return [];
        }
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            ShopInterface::EAV_OPTION_ID,
            $this->shopsHasNewProducts,
            'in'
        );
        $searchCriteria = $searchCriteria->create();
        $shopList = $this->shopRepository->getList($searchCriteria);

        return $shopList->getItems();
    }

    /**
     * Collect new products
     *
     * @return void
     */
    private function collectNewProducts(): void
    {
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productCollectionFactory->create();
        $this->isNewHelper->addNewFilter($productCollection);
        $productCollection->addAttributeToFilter(UpdateMiraklShopIdsAttribute::MIRAKL_SHOP_IDS, ['notnull' => true]);
        $productCollection->addFieldToSelect(UpdateMiraklShopIdsAttribute::MIRAKL_SHOP_IDS);

        $productCollection->setPageSize(self::COLLECTION_PAGE_SIZE);
        $pages = $productCollection->getLastPageNumber();
        for ($pageNum = 1; $pageNum <= $pages; $pageNum++) {
            $productCollection->setCurPage($pageNum);
            $this->collectMiraklShopIds($productCollection);
            $productCollection->clear();
        }
    }

    /**
     * Collect Mirakl Shop IDs from the product collection
     *
     * @param ProductCollection $productCollection
     *
     * @return void
     */
    private function collectMiraklShopIds(ProductCollection $productCollection): void
    {
        foreach ($productCollection->getItems() as $product) {
            $shopIds = $product->getMiraklShopIds() ?? '';
            if ($shopIds) {
                foreach (explode(',', $shopIds) as $shopId) {
                    $this->shopsHasNewProducts[$shopId] = $shopId;
                }
            }
        }
    }
}
