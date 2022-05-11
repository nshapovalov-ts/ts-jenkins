<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Rewrite\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Mirakl\Connector\Helper\Offer as MiraklOfferHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection as ConfigurableCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\CollectionFactory as ConfigurableCollectionFactory;
use Zend_Db_Select;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Core\Model\ResourceModel\Offer\State\CollectionFactory as StateCollectionFactory;
use Mirakl\Connector\Model\Offer as OfferModel;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\ResourceModel\Offer\State\Collection as OfferStateCollection;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\GroupRepositoryInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;
use Magento\Catalog\Model\ResourceModel\Config;

/**
 * Class Offer
 */
class Offer extends MiraklOfferHelper
{
    /** @var string */
    const ALL_GROUPS = 'all_groups';

    /** @var string */
    const OFFER_PRODUCT = 'offer_product';

    /**
     * @var array
     */
    private $productsOffers = [];

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ConnectorConfig
     */
    protected $connectorConfig;

    /**
     * @var StateCollectionFactory
     */
    protected $stateCollectionFactory;

    /**
     * @var OfferFactory
     */
    protected $offerFactory;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var OfferCollectionFactory
     */
    protected $offerCollectionFactory;

    /**
     * @var ShopFactory
     */
    protected $shopFactory;

    /**
     * @var ShopResourceFactory
     */
    protected $shopResourceFactory;

    /**
     * @var OfferStateCollection
     */
    protected $states;

    /**
     * @var StockStateInterface
     */
    protected $stockState;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var GroupRepositoryInterface
     */
    private $customerGroupRepository;

    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @var ConfigurableCollectionFactory
     */
    private $configurableCollectionFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $catalogConfig;

    /**
     * @param Context $context
     * @param OfferFactory $offerFactory
     * @param OfferResourceFactory $offerResourceFactory
     * @param OfferCollectionFactory $offerCollectionFactory
     * @param ConnectorConfig $connectorConfig
     * @param ShopFactory $shopFactory
     * @param ShopResourceFactory $shopResourceFactory
     * @param StateCollectionFactory $stateCollectionFactory
     * @param StockStateInterface $stockState
     * @param Session $customerSession
     * @param GroupRepositoryInterface $customerGroupRepository
     * @param SellerFilter $sellerFilter
     * @param ConfigurableCollectionFactory $configurableCollectionFactory
     * @param ProductFactory $productFactory
     * @param LoggerInterface $logger
     * @param Config $catalogConfig
     */
    public function __construct(
        Context $context,
        OfferFactory $offerFactory,
        OfferResourceFactory $offerResourceFactory,
        OfferCollectionFactory $offerCollectionFactory,
        ConnectorConfig $connectorConfig,
        ShopFactory $shopFactory,
        ShopResourceFactory $shopResourceFactory,
        StateCollectionFactory $stateCollectionFactory,
        StockStateInterface $stockState,
        Session $customerSession,
        GroupRepositoryInterface $customerGroupRepository,
        SellerFilter $sellerFilter,
        ConfigurableCollectionFactory $configurableCollectionFactory,
        ProductFactory $productFactory,
        LoggerInterface $logger,
        Config $catalogConfig
    ) {
        parent::__construct(
            $context,
            $offerFactory,
            $offerResourceFactory,
            $offerCollectionFactory,
            $connectorConfig,
            $shopFactory,
            $shopResourceFactory,
            $stateCollectionFactory,
            $stockState
        );

        $this->customerSession = $customerSession;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->sellerFilter = $sellerFilter;
        $this->configurableCollectionFactory = $configurableCollectionFactory;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
        $this->catalogConfig = $catalogConfig;
    }

    /**
     * Get available offers for a product
     *
     * @param Product $product
     * @param int|array $excludeOfferIds
     * @return  OfferCollection
     */
    public function getAvailableOffersForProduct(Product $product, $excludeOfferIds = null): OfferCollection
    {
        $cacheId = $this->getOfferCacheKey($product->getId(), $excludeOfferIds);
        if (isset($this->productsOffers[$cacheId])) {
            return $this->productsOffers[$cacheId];
        }

        $skus = [$product->getSku()];

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            /** @var Configurable $productType */
            $productType = $product->getTypeInstance();
            $childProducts = $productType->getUsedProductCollection($product)
                ->addAttributeToSelect('retail_price');

            $childProductsPerSku = [];
            /** @var Product $childProduct */
            foreach ($childProducts as $childProduct) {
                $skus[] = $childProduct->getSku();
                $childProductsPerSku[$childProduct->getSku()] = $childProduct;
            }
        }

        $currencyCode = $product->getStore()->getBaseCurrencyCode();
        $offers = $this->getAvailableOffersForProductSku($skus, $currencyCode, $excludeOfferIds, $product->getStoreId());

        /** @var OfferModel $offer */
        foreach ($offers as $offer) {
            $sku = $offer->getProductSku();
            if (isset($childProductsPerSku[$sku])) {
                $offer->setData(self::OFFER_PRODUCT, $childProductsPerSku[$sku]);
            } else {
                $offer->setData(self::OFFER_PRODUCT, $product);
            }
        }

        $this->productsOffers[$cacheId] = $offers;

        return $offers;
    }

    /**
     * @param int|string $productId
     * @param int|array|null $excludeOfferIds
     * @return string
     */
    private function getOfferCacheKey($productId, $excludeOfferIds)
    {
        return md5(serialize([$productId => $excludeOfferIds]));
    }

    /**
     * @param Product $product
     * @param int|array $excludeOfferIds
     */
    public function clearOfferCache(Product $product, $excludeOfferIds = null)
    {
        $cacheId = $this->getOfferCacheKey($product->getId(), $excludeOfferIds);
        if (isset($this->productsOffers[$cacheId])) {
            unset($this->productsOffers[$cacheId]);
        }
    }

    /**
     * Get Available Offers Ids For Products
     *
     * @param array|null $ids
     * @param string $currencyCode
     * @param int|array $excludeOfferIds
     * @param int|null $storeId
     * @param bool $isFillCache
     * @return array
     */
    public function getAvailableOffersForProducts(
        ?array $ids,
        string $currencyCode,
        $excludeOfferIds = null,
        $storeId = null,
        bool $isFillCache = false
    ): array {
        $allProductsOffers = [];

        if (empty($ids)) {
            return $allProductsOffers;
        }

        try {
            /** @var OfferCollection $offers */
            $offers = $this->offerCollectionFactory->create();
            $this->connection = $offers->getConnection();

            $childProducts = $this->getAllChildren($ids);

            $childProductsPerSku = [];
            $associatedConfigurableProducts = [];

            /** @var Product $childProduct */
            foreach ($childProducts as $childProduct) {
                $skus[] = $childProduct->getSku();
                $childProductsPerSku[$childProduct->getSku()] = $childProduct;

                $ids[] = $childProduct->getId();
                $associatedConfigurableProducts[(string) $childProduct->getId()][] = (string) $childProduct->getParentId();
            }

            $ids = array_unique($ids);

            //get all available offers for simple products
            $select = $offers->getSelect();
            $select->reset(Zend_Db_Select::COLUMNS);
            $select->columns(['main_table.*']);

            $offers->joinProductIds()
                ->addProductsEnabledFilter($storeId)
                ->addAvailableFilter();

            $offers->addFieldToFilter('products.entity_id', ['in' => $ids]);
            $offers->addCurrencyCodeFilter($currencyCode);

            if (!empty($excludeOfferIds)) {
                $offers->excludeOfferIdsFilter($excludeOfferIds);
            }

            $filteredShopOptionIds = $this->sellerFilter->getFilteredShopOptionIds();
            if (!empty($filteredShopOptionIds)) {
                $offers->getSelect()->where('shops.eav_option_id IN (?)', (array) $filteredShopOptionIds);
            }

            $offers->setOrder('state_code', 'ASC');

            //create result combining by groups
            /** @var OfferModel $offer */
            foreach ($offers as $offer) {
                $key = $offer->getProductId();
                $offerGroups = explode(',', $offer->getSegment());

                $sku = $offer->getProductSku();
                if (isset($childProductsPerSku[$sku])) {
                    $offer->setData(self::OFFER_PRODUCT, $childProductsPerSku[$sku]);
                }

                foreach ($offerGroups as $offerGroup) {
                    $offerGroup = $offerGroup == "" ? self::ALL_GROUPS : $offerGroup;
                    //for simple products
                    $allProductsOffers[$key][$offerGroup][$offer->getOfferId()] = $offer;

                    //for configurable  products
                    if (isset($associatedConfigurableProducts[$key])) {
                        $parents = $associatedConfigurableProducts[$key];
                        foreach ($parents as $parentKey) {
                            $allProductsOffers[$parentKey][$offerGroup][$offer->getOfferId()] = $offer;
                        }
                    }
                }
            }

            if ($isFillCache && !empty($allProductsOffers)) { //set all available offers for products to cache
                $this->setAllAvailableOffersToCache($offers, $allProductsOffers, $excludeOfferIds);
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return $allProductsOffers;
    }

    /**
     * Set All Available Offers To Cache
     *
     * @param Collection $collection
     * @param array $allProductsOffers
     * @param int|array|null $excludeOfferIds
     */
    private function setAllAvailableOffersToCache(Collection $collection, array $allProductsOffers, $excludeOfferIds)
    {
        $model = clone $collection;
        $model->removeAllItems();

        $code = $this->getGroupCode();
        $groupOfferFound = [];
        foreach ($allProductsOffers as $productId => $offerGroups) {
            $offers = clone $model;

            /** Add all offers for the current group first */
            if (!empty($offerGroups[$code])) {
                /** @var OfferModel $offer */
                foreach ($offerGroups[$code] as $offer) {
                    $sku = $offer->getProductSku();
                    $shopId = $offer->getShopId();
                    $groupOfferFound[$sku][$shopId] = true;

                    if (!$offers->getItemById($offer->getId())) {
                        $offers->addItem($offer);
                    }
                }
            }

            /** If some combination of shop_id and product_sku doesn't have a group offer, add the general offer */
            if (!empty($offerGroups[self::ALL_GROUPS])) {
                foreach ($offerGroups[self::ALL_GROUPS] as $offer) {
                    $sku = $offer->getProductSku();
                    $shopId = $offer->getShopId();

                    if (empty($groupOfferFound[$sku][$shopId])) {
                        if (!$offers->getItemById($offer->getId())) {
                            $offers->addItem($offer);
                        }
                    }
                }
            }

            $cacheId = $this->getOfferCacheKey($productId, $excludeOfferIds);
            $this->productsOffers[$cacheId] = $offers;
        }
    }

    /**
     * Get Group Code
     *
     * @return string
     */
    public function getGroupCode(): string
    {
        try {
            if ($this->customerSession->isLoggedIn()) {
                $groupId = $this->customerSession->getCustomer()->getGroupId();
                $group = $this->customerGroupRepository->getById($groupId);
                return $group->getCode();
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        return self::ALL_GROUPS;
    }

    /**
     * Get All Simple Products For Configurable
     *
     * @param array|null $ids
     * @return array
     */
    private function getAllChildren(?array $ids): array
    {
        /** @var ConfigurableCollection $childProducts */
        $childProducts = $this->configurableCollectionFactory->create();
        $connection = $childProducts->getConnection();
        $select = $childProducts->getSelect();
        $entityTypeId = $this->catalogConfig->getEntityTypeId();
        $select
            ->joinLeft(
                ['attr_retail_price' => $connection->getTableName('eav_attribute')],
                "attr_retail_price.attribute_code = 'retail_price' AND attr_retail_price.entity_type_id = $entityTypeId",
                []
            )->joinLeft(
                ['cpe_retail_price' => $connection->getTableName('catalog_product_entity_varchar')],
                "cpe_retail_price.entity_id = link_table.product_id AND cpe_retail_price.attribute_id = attr_retail_price.attribute_id AND cpe_retail_price.store_id = 0",
                ['retail_price' => 'cpe_retail_price.value']
            );

        if ($ids) {
            $select->where('link_table.parent_id in (?)', $ids);
        }

        $result = $connection->fetchAll($select);

        $items = [];
        foreach ($result as $productDate) {
            /** @var Product $product */
            $product = $this->productFactory->create();
            $product->setData($productDate);
            $items[] = $product;
        }

        return $items;
    }
}
