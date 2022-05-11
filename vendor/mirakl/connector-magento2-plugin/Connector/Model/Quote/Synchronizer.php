<?php
namespace Mirakl\Connector\Model\Quote;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Tax\Model\Calculation as TaxCalculation;
use Mirakl\Api\Helper\Shipping as Api;
use Mirakl\Connector\Helper\Config;
use Mirakl\Core\Helper\ShippingZone as ShippingZoneHelper;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;

class Synchronizer
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ShippingZoneHelper
     */
    protected $shippingZoneHelper;

    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @param   Api                 $api
     * @param   Config              $config
     * @param   ShippingZoneHelper  $shippingZoneHelper
     * @param   OfferCollector      $offerCollector
     * @param   Cache               $cache
     * @param   TaxCalculation      $taxCalculation
     */
    public function __construct(
        Api $api,
        Config $config,
        ShippingZoneHelper $shippingZoneHelper,
        OfferCollector $offerCollector,
        Cache $cache,
        TaxCalculation $taxCalculation
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->shippingZoneHelper = $shippingZoneHelper;
        $this->offerCollector = $offerCollector;
        $this->cache = $cache;
        $this->taxCalculation = $taxCalculation;
    }

    /**
     * @param   CartInterface   $quote
     * @return  bool
     */
    public function canComputeTaxes(CartInterface $quote)
    {
        if (!$this->config->isCalculateUSTaxes($quote->getStore()) || !$this->isShippingAddressComplete($quote)) {
            return false;
        }

        $shippingAddress = $this->getQuoteShippingAddress($quote);

        return $shippingAddress->getCountryId() === 'US'; // Compute taxes only for US shipments
    }

    /**
     * Returns shipping address of specified quote.
     * If address conditions data is empty, we try to retrieve it
     * from the customer attached to the quote object.
     *
     * @param   CartInterface   $quote
     * @return  AddressInterface
     */
    public function getQuoteShippingAddress(CartInterface $quote)
    {
        $defaultShippingAddressId = $quote->getCustomer()->getDefaultShipping();

        if ($defaultShippingAddressId && $this->isQuoteShippingAddressDataEmpty($quote)) {
            foreach ($quote->getCustomer()->getAddresses() as $address) {
                if ($address->getId() !== $defaultShippingAddressId) {
                    continue;
                }
                $data = [];
                foreach (array_keys($this->getQuoteShippingAddressAttributes()) as $attrCode) {
                    $method = 'get' . \Mirakl\pascalize($attrCode);
                    if (method_exists($address, $method)) {
                        if ($method == 'getRegion') {
                            if ($region = $address->getRegion()) {
                                $data[$attrCode] = is_object($region) ? $region->getRegion() : $region;
                            }
                        } else {
                            $data[$attrCode] = $address->$method();
                        }
                    }
                }
                $quote->getShippingAddress()->addData($data);
            }
        }

        if ($this->isQuoteShippingAddressDataEmpty($quote)) {
            $rateRequest = $this->taxCalculation->getRateRequest();
            $quote->getShippingAddress()
                ->setCountryId($rateRequest->getCountryId())
                ->setRegionId($rateRequest->getRegionId())
                ->setPostcode($rateRequest->getPostcode());
        }

        $shippingAddress = $quote->getShippingAddress();

        if (!$shippingAddress->getCountryId()) {
            // Fallback to default configured country if none defined in shipping address
            $defaultCountry = $this->config->getDefaultCountry($quote->getStore());
            $shippingAddress->setCountryId($defaultCountry);
        }

        return $shippingAddress;
    }

    /**
     * Returns current quote items grouped by order (SH02)
     *
     * @param   CartInterface   $quote
     * @return  CartItemInterface[]
     */
    public function getGroupedItems(CartInterface $quote)
    {
        $hash = $this->cache->getQuoteControlHash($quote);
        if ($cache = $this->cache->getCachedMethodResult(__METHOD__, $quote->getId(), $hash)) {
            return $cache;
        }

        $groupedItems = $this->offerCollector->getItemsWithoutOffer($quote);
        $itemsWithOffer = $this->offerCollector->getItemsWithOffer($quote);

        if (!empty($itemsWithOffer)) {
            $shippingFees = $this->getShippingFees($quote);
            if ($shippingFees && $shippingFees->count()) {
                foreach ($shippingFees as $orderShippingFee) {
                    /** @var OrderShippingFee $orderShippingFee */
                    if ($offers = $orderShippingFee->getOffers()) {
                        $groupedItems = array_merge(
                            $groupedItems,
                            array_intersect_key($itemsWithOffer, array_flip($offers->walk('getId')))
                        );
                    }
                }
                if ($shippingFees->getErrors()->count()) {
                    $groupedItems = array_merge(
                        $groupedItems,
                        array_intersect_key($itemsWithOffer, array_flip($shippingFees->getErrors()->walk('getOfferId')))
                    );
                }
            } else {
                $groupedItems = array_merge($groupedItems, $itemsWithOffer);
            }
        }

        $this->cache->setCachedMethodResult(__METHOD__, $quote->getId(), $groupedItems, $hash);

        return $groupedItems;
    }

    /**
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getGroupedOfferIds(CartInterface $quote)
    {
        $groupedOfferIds = [];
        $shippingFees = $this->getShippingFees($quote);
        /** @var OrderShippingFee $orderShippingFee */
        foreach ($shippingFees as $orderShippingFee) {
            if ($offers = $orderShippingFee->getOffers()) {
                $groupedOfferIds[] = $offers->walk('getId');
            }
        }

        return $groupedOfferIds;
    }

    /**
     * Returns attributes used for shipping zones address conditions
     *
     * @return  array
     */
    public function getQuoteShippingAddressAttributes()
    {
        return \Mirakl\Core\Model\Shipping\Zone\Condition\Address::getAttributes();
    }

    /**
     * Returns shipping zone code of specified quote object
     *
     * @param   CartInterface $quote
     * @return  string
     */
    public function getQuoteShippingZone(CartInterface $quote)
    {
        return $this->shippingZoneHelper->getShippingZoneCode(
            $this->getQuoteShippingAddress($quote), $quote->getStoreId()
        );
    }

    /**
     * @param   CartInterface   $quote
     * @return  array
     */
    public function getShippingAddressData(CartInterface $quote)
    {
        $shippingAddress = $this->getQuoteShippingAddress($quote);

        return [
            'street_1'     => $shippingAddress->getStreetLine(1),
            'city'         => $shippingAddress->getCity(),
            'zip_code'     => $shippingAddress->getPostcode(),
            'country_code' => $shippingAddress->getCountryModel()->getData('iso3_code'),
            'state'        => $shippingAddress->getRegionCode(),
        ];
    }

    /**
     * Returns specified quote shipping fees
     *
     * @param   CartInterface   $quote
     * @param   bool            $useCache
     * @param   int             $cacheLifetime
     * @return  OrderShippingFeeCollection
     */
    public function getShippingFees(CartInterface $quote, $useCache = false, $cacheLifetime = null)
    {
        if (!$quote->getMiraklShippingFees()) {
            $this->syncQuoteShippingInfo($quote, $useCache, $cacheLifetime);
        }

        return $quote->getMiraklShippingFees();
    }

    /**
     * Returns true if shipping address data of specified quote is empty
     * (check only attributes used for Mirakl shipping zones conditions)
     *
     * @param   CartInterface   $quote
     * @return  bool
     */
    public function isQuoteShippingAddressDataEmpty(CartInterface $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $checkAttributes = $this->getQuoteShippingAddressAttributes();
        $data = array_filter(array_intersect_key($shippingAddress->getData(), $checkAttributes));

        return empty($data);
    }

    /**
     * @param   CartInterface   $quote
     * @return  bool
     */
    public function isShippingAddressComplete(CartInterface $quote)
    {
        foreach ($this->getShippingAddressData($quote) as $value) {
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Synchronizes shipping fees with specified quote (calls the Mirakl platform)
     *
     * @param   CartInterface   $quote
     * @param   bool            $useCache
     * @param   int             $cacheLifetime
     * @return  CartInterface
     */
    public function syncQuoteShippingInfo(CartInterface $quote, $useCache = false, $cacheLifetime = null)
    {
        $zone         = $this->getQuoteShippingZone($quote);
        $computeTaxes = $this->canComputeTaxes($quote);
        $cacheKey     = $this->cache->getQuoteFeesCacheKey($quote, $zone);

        if ($fees = $this->cache->registry($cacheKey)) {
            goto UPDATE_QUOTE;
        }

        if ($useCache && ($fees = $this->cache->getCache()->load($cacheKey))) {
            $fees = unserialize($fees);
            goto REGISTER_FEES;
        }

        $locale              = $this->config->getLocale($quote->getStore());
        $offersWithQty       = $this->offerCollector->getOffersWithQty($quote);
        $shippingAddressData = [];

        if ($computeTaxes) {
            // Compute taxes in API SH02 if enabled in config and if shipping address is filled
            $shippingAddressData = $this->getShippingAddressData($quote);
        }

        \Magento\Framework\Profiler::start('MIRAKL: GET SHIPPING FEES');

        $fees = $this->api->getShippingFees($zone, $offersWithQty, $locale, $computeTaxes, $shippingAddressData);

        \Magento\Framework\Profiler::stop('MIRAKL: GET SHIPPING FEES');

        if ($useCache) {
            $this->cache->getCache()->save(serialize($fees), $cacheKey, $this->cache->getQuoteCacheTags($quote), $cacheLifetime);
        }

        REGISTER_FEES:
        $this->cache->register($cacheKey, $fees);

        UPDATE_QUOTE:
        $quote->setMiraklShippingFees($fees)
            ->setMiraklShippingZone($zone)
            ->setMiraklComputeTaxes($computeTaxes)
            ->setMiraklIsOfferInclTax($this->config->getOffersIncludeTax($quote->getStore()))
            ->setMiraklIsShippingInclTax($this->config->getShippingPricesIncludeTax($quote->getStore(), $quote));

        return $quote;
    }
}
