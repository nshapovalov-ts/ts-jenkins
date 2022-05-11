<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Model\Updater;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\TierPriceStorageInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ProductLinkResourceModel;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Retailplace\AttributesUpdater\Api\UpdaterInterface;
use Retailplace\AttributesUpdater\Model\Updater\AbstractUpdater;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_ExprFactory;


/**
 * Class TierPrice
 */
class TierPrice extends AbstractUpdater implements UpdaterInterface
{
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var int[] */
    private $groups = [];

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    private $customerGroupRepository;

    /** @var \Magento\Catalog\Api\TierPriceStorageInterface */
    private $tierPriceStorage;

    /**
     * TierPrice Constructor
     *
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $productLinkResourceModel
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepository
     * @param \Magento\Catalog\Api\TierPriceStorageInterface $tierPriceStorage
     * @param \Zend_Db_ExprFactory $exprFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ProductLinkResourceModel $productLinkResourceModel,
        ResourceConnection $resourceConnection,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        ProductRepositoryInterface $productRepository,
        GroupRepositoryInterface $customerGroupRepository,
        TierPriceStorageInterface $tierPriceStorage,
        Zend_Db_ExprFactory $exprFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $productLinkResourceModel,
            $resourceConnection,
            $attributeRepository,
            $searchCriteriaBuilderFactory,
            $offerRepository,
            $exprFactory,
            $scopeConfig,
            $logger
        );

        $this->productRepository = $productRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->tierPriceStorage = $tierPriceStorage;
    }

    /**
     * Run updater
     *
     * @param array $skus
     */
    public function run(array $skus = [])
    {
        $skusFromOffers = $this->getOfferSkus($skus);
        $this->updateGroupPrices($skusFromOffers, $skus);
    }

    /**
     * Extend Search Criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function extendOffersSearchCriteria(SearchCriteriaBuilder $searchCriteriaBuilder): SearchCriteriaBuilder
    {
        $searchCriteriaBuilder->addFilter(OfferInterface::SEGMENT, '', 'neq');

        return $searchCriteriaBuilder;
    }

    /**
     * Update Product Tier Prices Depends on Offer Segment
     *
     * @param string[] $skusFromOffers
     */
    private function updateGroupPrices(array $skusFromOffers, array $skus)
    {
        $productsId = $this->getProductsListWithEntityId($skusFromOffers);

        $insertData = [];
        $keepPricesData = [];
        foreach ($this->getAffectedOffersList() as $offer) {
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
     * Get Products Sku list in format [ProductSku => ProductEntityId]
     *
     * @param string[] $productsSku
     * @return int[]
     */
    private function getProductsListWithEntityId(array $productsSku): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(ProductInterface::SKU, $productsSku, 'in')
            ->create();

        $products = $this->productRepository->getList($searchCriteria);
        $productsId = [];
        foreach ($products->getItems() as $product) {
            $productsId[$product->getSku()] = $product->getId();
        }

        return $productsId;
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
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $groupSearchCriteria = $searchCriteriaBuilder
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
}
