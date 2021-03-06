<?php
namespace Mirakl\MMP\FrontOperator\Domain\Product\Offer;

use Mirakl\Core\Domain\MiraklObject;
use Mirakl\MMP\Common\Domain\Category\LogisticClass;
use Mirakl\MMP\Common\Domain\Collection\AdditionalFieldValueCollection;
use Mirakl\MMP\Common\Domain\Collection\Offer\Price\OfferPricesCollection;
use Mirakl\MMP\Common\Domain\Discount;
use Mirakl\MMP\Common\Domain\Offer\OfferAvailability;
use Mirakl\MMP\Common\Domain\Offer\OfferMinimumShipping;
use Mirakl\MMP\Common\Domain\Offer\ProductInfoWithRefs;
use Mirakl\MMP\Common\Domain\Offer\Price\OfferPricing;

/**
 * @method  bool                            getActive()
 * @method  $this                           setActive(bool $active)
 * @method  bool                            isActive()
 * @method  bool                            getAllowQuoteRequests()
 * @method  $this                           setAllowQuoteRequests(bool $allowQuoteRequests)
 * @method  OfferPricesCollection           getAllPrices()
 * @method  $this                           setAllPrices(array|OfferPricesCollection $prices)
 * @method  OfferPricing                    getApplicablePricing()
 * @method  $this                           setApplicablePricing(array|OfferPricing $prices)
 * @method  OfferAvailability               getAvailability()
 * @method  $this                           setAvailability(array|OfferAvailability $availability)
 * @method  AdditionalFieldValueCollection  getAdditionalFields()
 * @method  $this                           setAdditionalFields(array|AdditionalFieldValueCollection $additionalFields)
 * @method  array                           getChannels()
 * @method  $this                           setChannels(array $channels)
 * @method  string                          getCurrencyIsoCode()
 * @method  $this                           setCurrencyIsoCode(string $currencyIsoCode)
 * @method  string                          getDescription()
 * @method  $this                           setDescription(string $description)
 * @method  Discount                        getDiscount()
 * @method  $this                           setDiscount(array|Discount $discount)
 * @method  int                             getFavoriteRank()
 * @method  $this                           setFavoriteRank(int $favoriteRank)
 * @method  array                           getInactivityReasons()
 * @method  $this                           setInactivityReasons(array $inactivityReasons)
 * @method  int                             getLeadtimeToShip() (in days)
 * @method  $this                           setLeadtimeToShip(int $leadtimeToShip)
 * @method  LogisticClass                   getLogisticClass()
 * @method  $this                           setLogisticClass(array|LogisticClass $logisticClass)
 * @method  int                             getMaxOrderQuantity()
 * @method  $this                           setMaxOrderQuantity(int $maxOrderQuantity)
 * @method  int                             getMinOrderQuantity()
 * @method  $this                           setMinOrderQuantity(int $minOrderQuantity)
 * @method  int                             getPackageQuantity()
 * @method  $this                           setPackageQuantity(int $packageQuantity)
 * @method  int                             getMinQuantityAlert()
 * @method  $this                           setMinQuantityAlert(int $minQuantityAlert)
 * @method  OfferMinimumShipping            getMinShipping()
 * @method  $this                           setMinShipping(array|OfferMinimumShipping $minShipping)
 * @method  int                             getNbEvaluation()
 * @method  $this                           setNbEvaluation(int $nbEvaluation)
 * @method  string                          getOfferId()
 * @method  $this                           setOfferId(string $id)
 * @method  bool                            getPremium()
 * @method  $this                           setPremium(bool $flag)
 * @method  bool                            isPremium()
 * @method  bool                            getProfessional()
 * @method  $this                           setProfessional(bool $flag)
 * @method  bool                            isProfessional()
 * @method  float                           getPrice()
 * @method  $this                           setPrice(float $price)
 * @method  string                          getPriceAdditionalInfo()
 * @method  $this                           setPriceAdditionalInfo(string $priceAdditionalInfo)
 * @method  ProductInfoWithRefs             getProduct()
 * @method  $this                           setProduct(array|ProductInfoWithRefs $productInfoWithRefs)
 * @method  string                          getProductTaxCode()
 * @method  $this                           setProductTaxCode(string $productTaxCode)
 * @method  int                             getQuantity()
 * @method  $this                           setQuantity(int $quantity)
 * @method  int                             getShopGrade()
 * @method  $this                           setShopGrade(int $shopGrade)
 * @method  int                             getShopId()
 * @method  $this                           setShopId(int $shopId)
 * @method  string                          getShopName()
 * @method  $this                           setShopName(string $shopName)
 * @method  string                          getStateCode()
 * @method  $this                           setStateCode(string $stateCode)
 * @method  float                           getTotalPrice()
 * @method  $this                           setTotalPrice(float $totalPrice)
 */
class OfferOnProduct extends MiraklObject
{
    /**
     * @var array
     */
    protected static $mapping = [
        'id'                            => 'offer_id',
        'product_sku'                   => 'product/sku',
        'product_title'                 => 'product/title',
        'product_references'            => 'product/references',
        'category_code'                 => 'product/category/code',
        'category_label'                => 'product/category/label',
        'category_type'                 => 'product/category/type',
        'min_shipping_price'            => 'min_shipping/price',
        'min_shipping_price_additional' => 'min_shipping/price_additional',
        'min_shipping_type'             => 'min_shipping/type_code',
        'min_shipping_zone'             => 'min_shipping/zone_code',
        'available_start_date'          => 'availability/start_date',
        'available_end_date'            => 'availability/end_date',
        'offer_additional_fields'       => 'additional_fields',
        'is_professional'               => 'professional',
        'is_premium'                    => 'premium',
    ];

    /**
     * @var array
     */
    protected static $dataTypes = [
        'additional_fields'     => [AdditionalFieldValueCollection::class, 'create'],
        'availability'          => [OfferAvailability::class, 'create'],
        'discount'              => [Discount::class, 'create'],
        'logistic_class'        => [LogisticClass::class, 'create'],
        'min_shipping'          => [OfferMinimumShipping::class, 'create'],
        'product'               => [ProductInfoWithRefs::class, 'create'],
        'all_prices'            => [OfferPricesCollection::class, 'create'],
        'applicable_pricing'    => [OfferPricing::class, 'create'],
    ];

    /**
     * @return  string
     */
    public function getId()
    {
        return $this->getOfferId();
    }

    /**
     * @param   mixed   $product
     * @return  $this
     */
    public function setProductReferences($product)
    {
        if (is_array($product)) {
            $product = ProductInfoWithRefs::create(['references' => $product]);
        }

        return $this->setProduct($product);
    }
}