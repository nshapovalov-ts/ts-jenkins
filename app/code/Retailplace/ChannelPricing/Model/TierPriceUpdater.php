<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\TierPriceStorageInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Class TierPriceUpdater
 */
class TierPriceUpdater
{
    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var int[] */
    private $groups = [];

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface[] */
    private $offersList;

    /** @var \Magento\Catalog\Api\TierPriceStorageInterface */
    private $tierPriceStorage;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * TierPriceUpdater constructor.
     *
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Api\TierPriceStorageInterface $tierPriceStorage
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        OfferRepositoryInterface $offerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        GroupRepositoryInterface $customerGroupRepository,
        ResourceConnection $resourceConnection,
        TierPriceStorageInterface $tierPriceStorage,
        LoggerInterface $logger
    ) {
        $this->offerRepository = $offerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->resourceConnection = $resourceConnection;
        $this->tierPriceStorage = $tierPriceStorage;
        $this->logger = $logger;
    }

    /**
     * Update Product Tier Prices Depends on Offer Segment
     *
     * @param string[] $skus
     */
    public function updateGroupPrices(array $skus)
    {
        $productsSku = [];
        $offersList = $this->getOffersList($skus);
        foreach ($offersList as $offer) {
            $productsSku[] = $offer->getProductSku();
        }

        $productsId = $this->getProductsListWithEntityId($productsSku);

        $insertData = [];
        $keepPricesData = [];
        foreach ($offersList as $offer) {
            $groups = explode(',', $offer->getSegment());
            foreach ($groups as $group) {
                $groupId = $this->getGroupBySegment($group);
                if ($groupId && !empty($productsId[$offer->getProductSku()])) {
                    if (isset($keepPricesData[$offer->getProductSku()])) {
                        $keepPricesData[$offer->getProductSku()][] = $group;
                    } else {
                        $keepPricesData[$offer->getProductSku()] = [$group];
                    }

                    $insertData[] = [
                        'entity_id' => $productsId[$offer->getProductSku()],
                        'all_groups' => 0,
                        'customer_group_id' => $groupId,
                        'value' => $offer->getPrice(),
                        'website_id' => 0
                    ];
                }
            }
        }

        $this->removeUnusedPrices($keepPricesData, $skus);

        if (count($insertData)) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $this->resourceConnection->getTableName('catalog_product_entity_tier_price'),
                $insertData
            );
        }
    }

    /**
     * Remove Unused Tier Prices
     *
     * @param array[] $keepPricesData
     * @param string[] $skus
     */
    private function removeUnusedPrices(array $keepPricesData, array $skus)
    {
        $tierPricesToDelete = [];
        try {
            $tierPrices = $this->tierPriceStorage->get($skus);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $tierPrices = [];
        }

        foreach ($tierPrices as $tierPrice) {
            if (isset($keepPricesData[$tierPrice->getSku()])) {
                $groupsArray = $keepPricesData[$tierPrice->getSku()];
                if (!in_array($tierPrice->getCustomerGroup(), $groupsArray)) {
                    $tierPricesToDelete[] = $tierPrice;
                }
            }
        }

        if (count($tierPricesToDelete)) {
            $this->tierPriceStorage->delete($tierPricesToDelete);
        }
    }

    /**
     * Get Products Sku list in format [ProductSku => ProductEntityId]
     *
     * @param string[] $productsSku
     * @return int[]
     */
    private function getProductsListWithEntityId(array $productsSku): array
    {
        $productSearchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ProductInterface::SKU, $productsSku, 'in')
            ->create();

        $products = $this->productRepository->getList($productSearchCriteria);
        $productsId = [];
        foreach ($products->getItems() as $product) {
            $productsId[$product->getSku()] = $product->getId();
        }

        return $productsId;
    }

    /**
     * Get Offers List by SKU list
     *
     * @param string[] $skus
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface[]
     */
    private function getOffersList(array $skus): array
    {
        if (!$this->offersList) {

            $offerSearchCriteria = $this->searchCriteriaBuilder
                ->addFilter(OfferInterface::PRODUCT_SKU, $skus, 'in')
                ->addFilter(OfferInterface::SEGMENT, '', 'neq')
                ->create();

            $offers = $this->offerRepository->getList($offerSearchCriteria);
            $this->offersList = $offers->getItems();
        }

        return $this->offersList;
    }

    /**
     * Get Customer Group by Offer Segment
     *
     * @param string $miraklSegment
     * @return int|null
     */
    private function getGroupBySegment(string $miraklSegment): ?int
    {
        if (!isset($this->groups[$miraklSegment])) {
            $groupSearchCriteria = $this->searchCriteriaBuilder
                ->addFilter(GroupInterface::CODE, $miraklSegment)
                ->create();

            $groupId = null;
            try {
                $group = $this->customerGroupRepository->getList($groupSearchCriteria);
                foreach ($group->getItems() as $group) {
                    $groupId = (int) $group->getId();
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }

            $this->groups[$miraklSegment] = $groupId;
        }

        return $this->groups[$miraklSegment];
    }
}
