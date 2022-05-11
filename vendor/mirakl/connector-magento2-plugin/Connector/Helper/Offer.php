<?php
namespace Mirakl\Connector\Helper;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Offer as OfferModel;
use Mirakl\Connector\Model\OfferFactory;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use Mirakl\Core\Model\ResourceModel\Offer\State\Collection as OfferStateCollection;
use Mirakl\Core\Model\ResourceModel\Offer\State\CollectionFactory as StateCollectionFactory;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\Core\Model\Shop as ShopModel;

class Offer extends AbstractHelper
{
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
     * @param   Context                 $context
     * @param   OfferFactory            $offerFactory
     * @param   OfferResourceFactory    $offerResourceFactory
     * @param   OfferCollectionFactory  $offerCollectionFactory
     * @param   ConnectorConfig         $connectorConfig
     * @param   ShopFactory             $shopFactory
     * @param   ShopResourceFactory     $shopResourceFactory
     * @param   StateCollectionFactory  $stateCollectionFactory
     * @param   StockStateInterface     $stockState
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
        StockStateInterface $stockState
    ) {
        parent::__construct($context);
        $this->offerFactory           = $offerFactory;
        $this->offerResourceFactory   = $offerResourceFactory;
        $this->offerCollectionFactory = $offerCollectionFactory;
        $this->connectorConfig        = $connectorConfig;
        $this->shopFactory            = $shopFactory;
        $this->shopResourceFactory    = $shopResourceFactory;
        $this->stateCollectionFactory = $stateCollectionFactory;
        $this->stockState             = $stockState;
    }

    /**
     * Return the number of available offers for given product
     *
     * @param   Product $product
     * @return  int
     */
    public function countAvailableOffersForProduct($product)
    {
        return count($this->getAvailableOffersForProduct($product));
    }

    /**
     * Returns offer state collection
     *
     * @return  OfferStateCollection
     */
    public function getAllConditions()
    {
        if (null === $this->states) {
            $this->states = $this->stateCollectionFactory->create();
        }

        return $this->states;
    }

    /**
     * Get available offers for a product
     *
     * @param   Product     $product
     * @param   int|array   $excludeOfferIds
     * @return  OfferCollection
     */
    public function getAvailableOffersForProduct(Product $product, $excludeOfferIds = null)
    {
        static $productsOffers = [];
        $cacheId = md5(serialize([$product->getId() => $excludeOfferIds]));
        if (isset($productsOffers[$cacheId])) {
            return $productsOffers[$cacheId];
        }

        $skus = [$product->getSku()];

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            /** @var Configurable $productType */
            $productType = $product->getTypeInstance();
            $skus = array_merge($skus, $productType->getUsedProductCollection($product)->getColumnValues('sku'));
        }

        $currencyCode = $product->getStore()->getBaseCurrencyCode();
        $offers = $this->getAvailableOffersForProductSku($skus, $currencyCode, $excludeOfferIds, $product->getStoreId());

        $productsOffers[$cacheId] = $offers;

        return $offers;
    }

    /**
     * Get available offers for a product sku and a currency code
     *
     * @param   string      $sku
     * @param   string      $currencyCode
     * @param   int|array   $excludeOfferIds
     * @param   int|null    $storeId
     * @return  OfferCollection
     */
    public function getAvailableOffersForProductSku($sku, $currencyCode, $excludeOfferIds = null, $storeId = null)
    {
        /** @var OfferCollection $collection */
        $collection = $this->offerCollectionFactory->create();

        $collection->joinProductIds()
            ->addProductsEnabledFilter($storeId)
            ->addAvailableFilter()
            ->addProductSkuFilter($sku)
            ->addCurrencyCodeFilter($currencyCode);

        if (!empty($excludeOfferIds)) {
            $collection->excludeOfferIdsFilter($excludeOfferIds);
        }

        $collection->setOrder('state_code', 'ASC');

        return $collection;
    }

    /**
     * Returns offer state matching specified state id
     *
     * @param   int $stateId
     * @return  string
     */
    public function getConditionNameById($stateId)
    {
        /** @var \Mirakl\Core\Model\Offer\State $state */
        $state = $this->getAllConditions()->getItemById($stateId);

        return $state ? $state->getName() : $stateId;
    }

    /**
     * Retrieve offer based on given offer id
     *
     * @param   string  $offerId
     * @return  OfferModel
     */
    public function getOfferById($offerId)
    {
        $offer = $this->offerFactory->create();
        $this->offerResourceFactory->create()->load($offer, $offerId);

        return $offer;
    }

    /**
     * Returns condition name of specified offer
     *
     * @param   OfferModel  $offer
     * @return  string
     */
    public function getOfferCondition(OfferModel $offer)
    {
        return $offer ? $this->getConditionNameById($offer->getStateId()) : '';
    }

    /**
     * @param   OfferModel  $offer
     * @param   int|null    $qty
     * @return  float
     */
    public function getOfferFinalPrice(OfferModel $offer, $qty = null)
    {
        $price = (float) $offer->getPrice();
        $discountPrice = 0;

        if ($qty && $offer->isDiscountPriceValid()) {
            // Check if a discount price is valid for current quantity
            $discount = $offer->getDiscount();
            if ($ranges = $discount->getRanges()) {
                /** @var \Mirakl\MMP\Common\Domain\DiscountRange $range */
                foreach (array_reverse($ranges->getItems()) as $range) {
                    if ($qty >= $range->getQuantityThreshold()) {
                        $discountPrice = (float) $range->getPrice();
                        break;
                    }
                }
            }
        }

        if ($qty > 1) {
            // Check if a price range is valid for current quantity
            $ranges = $offer->getPriceRanges();
            foreach (array_reverse($ranges->getItems()) as $range) {
                if ($qty >= $range->getQuantityThreshold() && $range->getPrice() <= $price) {
                    $price = (float) $range->getPrice();
                    break;
                }
            }
        }

        return ($discountPrice > 0 && $discountPrice <= $price) ? $discountPrice : $price;
    }

    /**
     * Returns shop of specified offer if available
     *
     * @param   OfferModel  $offer
     * @return  ShopModel
     */
    public function getOfferShop(OfferModel $offer)
    {
        /** @var ShopModel $shop */
        $shop = $this->shopFactory->create();
        $this->shopResourceFactory->create()->load($shop, $offer->getShopId());

        return $shop;
    }

    /**
     * Returns true if product has available offers
     *
     * @param   Product $product
     * @return  bool
     */
    public function hasAvailableOffersForProduct($product)
    {
        return $this->countAvailableOffersForProduct($product) > 0;
    }

    /**
     * Get the StockState object
     *
     * @return  StockStateInterface
     */
    public function getStockState()
    {
        return $this->stockState;
    }
}
