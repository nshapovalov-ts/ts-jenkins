<?php
namespace Mirakl\FrontendDemo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote as QuoteObject;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Mirakl\Api\Model\Shipping\Rates\Error as ShippingRatesError;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\Core\Exception\ShippingZoneNotFound;
use Mirakl\FrontendDemo\Model\Quote\Loader as QuoteLoader;
use Mirakl\MMP\Common\Domain\Shipping\ShippingType;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
use Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer;

class Quote extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var QuoteLoader
     */
    protected $quoteLoader;

    /**
     * @var QuoteSynchronizer
     */
    protected $quoteSynchronizer;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * @param   Context             $context
     * @param   Config              $config
     * @param   QuoteLoader         $quoteLoader
     * @param   QuoteSynchronizer   $quoteSynchronizer
     * @param   QuoteUpdater        $quoteUpdater
     * @param   QuoteHelper         $quoteHelper
     * @param   OfferCollector      $offerCollector
     */
    public function __construct(
        Context $context,
        Config $config,
        QuoteLoader $quoteLoader,
        QuoteSynchronizer $quoteSynchronizer,
        QuoteUpdater $quoteUpdater,
        QuoteHelper $quoteHelper,
        OfferCollector $offerCollector
    ) {
        parent::__construct($context);

        $this->config = $config;
        $this->quoteLoader = $quoteLoader;
        $this->quoteSynchronizer = $quoteSynchronizer;
        $this->quoteUpdater = $quoteUpdater;
        $this->quoteHelper = $quoteHelper;
        $this->offerCollector = $offerCollector;
    }

    /**
     * @param   QuoteObject|null    $quote
     * @return  bool
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::canComputeTaxes()
     */
    public function canComputeTaxes(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->canComputeTaxes($quote);
    }

    /**
     * Returns current quote items grouped by order (SH02)
     *
     * @param   QuoteObject $quote
     * @return  QuoteItem[]
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::getGroupedItems()
     */
    public function getGroupedItems(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->getGroupedItems($quote);
    }

    /**
     * @param   QuoteObject $quote
     * @return  array
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::getGroupedOfferIds()
     */
    public function getGroupedOfferIds(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->getGroupedOfferIds($quote);
    }

    /**
     * Returns selected shipping fee type
     *
     * @param   QuoteItem   $item
     * @return  ShippingFeeType
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::getItemSelectedShippingType()
     */
    public function getItemSelectedShippingType(QuoteItem $item)
    {
        return $this->quoteUpdater->getItemSelectedShippingType($item);
    }

    /**
     * Returns shipping fee offer of given item (item that is a Mirakl offer)
     *
     * @param   QuoteItem   $item
     * @return  ShippingRateOffer
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::getItemShippingRateOffer()
     */
    public function getItemShippingRateOffer(QuoteItem $item)
    {
        return $this->quoteUpdater->getItemShippingRateOffer($item);
    }

    /**
     * Returns shipping fee type by code
     *
     * @param   QuoteItem   $item
     * @param   string      $shippingTypeCode
     * @return  ShippingFeeType
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::getItemShippingTypeByCode()
     */
    public function getItemShippingTypeByCode(QuoteItem $item, $shippingTypeCode)
    {
        return $this->quoteUpdater->getItemShippingTypeByCode($item, $shippingTypeCode);
    }


    /**
     * Returns available shipping types for given quote item
     *
     * @param   QuoteItem   $item
     * @return  ShippingFeeTypeCollection
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::getItemShippingTypes()
     */
    public function getItemShippingTypes(QuoteItem $item)
    {
        return $this->quoteUpdater->getItemShippingTypes($item);
    }

    /**
     * Returns current offers in quote
     *
     * @param   QuoteObject $quote
     * @return  array
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\OfferCollector::getItemsWithOffer()
     */
    public function getItemsWithOffer(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->offerCollector->getItemsWithOffer($quote);
    }

    /**
     * Returns operator quote items
     *
     * @param   QuoteObject $quote
     * @return  array
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\OfferCollector::getItemsWithoutOffer()
     */
    public function getItemsWithoutOffer(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->offerCollector->getItemsWithoutOffer($quote);
    }

    /**
     * Returns order shipping fee of given quote item (that is linked to a Mirakl offer)
     *
     * @param   QuoteItem   $item
     * @return  OrderShippingFee
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::getItemOrderShippingFee()
     */
    public function getItemOrderShippingFee(QuoteItem $item)
    {
        return $this->quoteUpdater->getItemOrderShippingFee($item);
    }

    /**
     * Returns offers in cart with quantity
     *
     * @param   QuoteObject $quote
     * @return  array
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\OfferCollector::getOffersWithQty()
     */
    public function getOffersWithQty(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->offerCollector->getOffersWithQty($quote);
    }

    /**
     * Returns current quote
     *
     * @return  QuoteObject
     */
    public function getQuote()
    {
        return $this->quoteLoader->getQuote();
    }

    /**
     * @param   QuoteObject $quote
     * @return  array
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\OfferCollector::getQuoteItems()
     */
    public function getQuoteItems(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->offerCollector->getQuoteItems($quote);
    }

    /**
     * @param   QuoteObject $quote
     * @return  int
     */
    public function getQuoteItemsCount(QuoteObject $quote = null)
    {
        $count = 0;
        foreach ($this->getQuoteItems($quote) as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns shipping zone code of current or specified quote object
     *
     * @param   QuoteObject $quote
     * @return  string
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::getQuoteShippingZone()
     */
    public function getQuoteShippingZone(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->getQuoteShippingZone($quote);
    }

    /**
     * @param   QuoteObject|null    $quote
     * @return  array
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::getShippingAddressData()
     */
    public function getShippingAddressData(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->getShippingAddressData($quote);
    }

    /**
     * Returns current quote shipping fees
     *
     * @param   QuoteObject $quote
     * @return  OrderShippingFeeCollection
     */
    public function getShippingFees(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->getShippingFees(
            $quote, $this->config->isShippingFeesCacheEnabled(), $this->config->getShippingFeesCacheLifetime()
        );
    }

    /**
     * Returns true if given quote contains ONLY Mirakl products
     *
     * @param   QuoteObject $quote
     * @return  bool
     */
    public function isFullMiraklQuote(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteHelper->isFullMiraklQuote($quote);
    }

    /**
     * Returns true if given quote contains SOME Mirakl products
     *
     * @param   QuoteObject $quote
     * @return  bool
     */
    public function isMiraklQuote(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteHelper->isMiraklQuote($quote);
    }

    /**
     * Returns true if shipping address data of specified quote is empty
     * (check only attributes used for Mirakl shipping zones conditions)
     *
     * @param   QuoteObject|null    $quote
     * @return  bool
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::isQuoteShippingAddressDataEmpty()
     */
    public function isQuoteShippingAddressDataEmpty(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->isQuoteShippingAddressDataEmpty($quote);
    }

    /**
     * @param   QuoteObject|null    $quote
     * @return  false
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Synchronizer::isShippingAddressComplete()
     */
    public function isShippingAddressComplete(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        return $this->quoteSynchronizer->isShippingAddressComplete($quote);
    }

    /**
     * Reset quote item shipping type
     *
     * @param   QuoteItem   $item
     * @return  $this
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::resetItemShippingType()
     */
    public function resetItemShippingType(QuoteItem $item)
    {
        $this->quoteUpdater->resetItemShippingType($item);

        return $this;
    }

    /**
     * Reset all quote shipping types
     *
     * @param   QuoteObject $quote
     * @return  $this
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::resetQuoteShippingTypes()
     */
    public function resetQuoteShippingTypes(QuoteObject $quote = null)
    {
        $quote = $quote ?: $this->getQuote();

        $this->quoteUpdater->resetQuoteShippingTypes($quote);

        return $this;
    }

    /**
     * Update quote item shipping fee information
     *
     * @param   QuoteItem       $item
     * @param   ShippingType    $shippingType
     * @return  $this
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::setItemShippingType()
     */
    public function setItemShippingType(QuoteItem $item, ShippingType $shippingType)
    {
        $this->quoteUpdater->setItemShippingType($item, $shippingType);

        return $this;
    }

    /**
     * Update quote item shipping type information
     *
     * @param   QuoteItem   $item
     * @param   string      $shippingTypeCode
     * @return  $this
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::setItemShippingTypeByCode()
     */
    public function setItemShippingTypeByCode(QuoteItem $item, $shippingTypeCode)
    {
        $this->quoteUpdater->setItemShippingTypeByCode($item, $shippingTypeCode);

        return $this;
    }

    /**
     * Update quote item shipping fee amount
     *
     * @param   QuoteItem           $item
     * @param   ShippingRateOffer   $shippingRateOffer
     * @return  $this
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::setItemShippingFee()
     */
    public function setItemShippingFee(QuoteItem $item, ShippingRateOffer $shippingRateOffer)
    {
        $this->quoteUpdater->setItemShippingFee($item, $shippingRateOffer);

        return $this;
    }

    /**
     * Synchronizes shipping fees with current checkout session (calls the Mirakl platform)
     *
     * @param   QuoteObject $quote
     * @param   bool        $useCache
     * @return  $this
     */
    public function syncQuoteShippingInfo(QuoteObject $quote = null, $useCache = false)
    {
        try {
            $this->quoteSynchronizer->syncQuoteShippingInfo($quote, $useCache, $this->config->getShippingFeesCacheLifetime());

        } catch (\GuzzleHttp\Exception\ServerException $e) { // (5xx codes)
            $response = \Mirakl\parse_json_response($e->getResponse());
            $quote->setHasError(true);
            $quote->addMessage(__('Shipping charges calculation has encountered a server error: %1', $e->getCode()));
            $this->_logger->critical($response['message']);
        } catch (\GuzzleHttp\Exception\ClientException $e) { // (4xx codes)
            $response = \Mirakl\parse_json_response($e->getResponse());
            if (isset($response['code']) && ShippingRatesError::isProviderError($response['code'])) {
                $quote->addMessage(__('Shipping charges calculation has encountered an error. Please modify the shipping address.'));
            } else {
                $quote->addMessage(__('Shipping charges calculation has encountered an error: %1', $e->getCode()));
            }
            $quote->setHasError(true);
            $this->_logger->critical($response['message']);
        } catch (ShippingZoneNotFound $e) {
            $quote->setHasError(true);
            $quote->addMessage($e->getMessage());
        } catch (\Exception $e) {
            $quote->setHasError(true);
            $quote->addMessage(__('There is no shipping zone defined in your configuration.'));
            $this->_logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * @param   array       $offersShippingTypes
     * @param   QuoteObject $quote
     * @param   bool        $resetAll
     * @param   bool        $saveItem
     * @return  $this
     * @deprecated
     * @see \Mirakl\Connector\Model\Quote\Updater::updateOffersShippingTypes()
     */
    public function updateOffersShippingTypes(array $offersShippingTypes, QuoteObject $quote = null, $resetAll = true, $saveItem = false)
    {
        $quote = $quote ?: $this->getQuote();

        $this->quoteUpdater->updateOffersShippingTypes($offersShippingTypes, $quote, $resetAll, $saveItem);

        return $this;
    }
}
