<?php
namespace Mirakl\FrontendDemo\Plugin\Model\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\AddressFactory as QuoteAddressResourceFactory;
use Magento\Quote\Model\ShippingMethodManagementInterface;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\FrontendDemo\Helper\Quote\Item as QuoteItemHelper;
use Mirakl\FrontendDemo\Model\Quote\Updater as QuoteUpdater;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;

class ShippingMethodManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteHelper
     */
    private $quoteHelper;

    /**
     * @var QuoteItemHelper
     */
    private $quoteItemHelper;

    /**
     * @var QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var ConnectorConfig
     */
    private $connectorConfig;

    /**
     * @var QuoteAddressResourceFactory
     */
    private $quoteAddressResourceFactory;

    /**
     * @var QuoteSynchronizer
     */
    private $quoteSynchronizer;

    /**
     * @param   CartRepositoryInterface     $quoteRepository
     * @param   QuoteHelper                 $quoteHelper
     * @param   QuoteItemHelper             $quoteItemHelper
     * @param   QuoteUpdater                $quoteUpdater
     * @param   PriceCurrencyInterface      $priceCurrency
     * @param   ConnectorConfig             $connectorConfig
     * @param   QuoteAddressResourceFactory $quoteAddressResourceFactory
     * @param   QuoteSynchronizer           $quoteSynchronizer
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteHelper $quoteHelper,
        QuoteItemHelper $quoteItemHelper,
        QuoteUpdater $quoteUpdater,
        PriceCurrencyInterface $priceCurrency,
        ConnectorConfig $connectorConfig,
        QuoteAddressResourceFactory $quoteAddressResourceFactory,
        QuoteSynchronizer $quoteSynchronizer
    ) {
        $this->quoteRepository             = $quoteRepository;
        $this->quoteHelper                 = $quoteHelper;
        $this->quoteItemHelper             = $quoteItemHelper;
        $this->quoteUpdater                = $quoteUpdater;
        $this->priceCurrency               = $priceCurrency;
        $this->connectorConfig             = $connectorConfig;
        $this->quoteAddressResourceFactory = $quoteAddressResourceFactory;
        $this->quoteSynchronizer           = $quoteSynchronizer;
    }

    /**
     * @param   ShippingMethodManagementInterface   $subject
     * @param   \Closure                            $proceed
     * @param   string                              $cartId
     * @param   AddressInterface                    $address
     * @return  ShippingMethodInterface[]
     */
    public function aroundEstimateByExtendedAddress(
        ShippingMethodManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        AddressInterface $address
    ) {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if ($quote->isVirtual() || !$quote->getItemsCount()) {
            return [];
        }

        /** @var Quote\Address $address */

        // Override quote address data with shipping estimation data
        $data = array_intersect_key($address->getData(), $this->quoteSynchronizer->getQuoteShippingAddressAttributes());
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($data);
        $this->quoteAddressResourceFactory->create()->save($shippingAddress);

        $this->quoteUpdater->synchronize($quote);

        // Retrieve default shipping methods
        $shippingMethods = $proceed($cartId, $address);

        $this->handleMiraklShippingTypes($quote, $shippingMethods, $shippingAddress);

        return $shippingMethods;
    }

    /**
     * @param   ShippingMethodManagementInterface   $subject
     * @param   \Closure                            $proceed
     * @param   int                                 $cartId
     * @param   int                                 $addressId
     * @return  array
     */
    public function aroundEstimateByAddressId(
        ShippingMethodManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        $addressId
    ) {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        if ($quote->isVirtual() || !$quote->getItemsCount()) {
            return [];
        }

        // Retrieve default shipping methods
        $shippingMethods = $proceed($cartId, $addressId);

        $this->handleMiraklShippingTypes($quote, $shippingMethods);

        return $shippingMethods;
    }

    /**
     * @param   Quote               $quote
     * @param   array               $shippingMethods
     * @param   Quote\Address|null  $shippingAddress
     */
    private function handleMiraklShippingTypes(Quote $quote, array &$shippingMethods, $shippingAddress = null)
    {
        // If shopping cart contains only Mirakl offers, do not show default shipping methods
        // because they are not needed. Allow only free shipping.
        if ($this->quoteHelper->isFullMiraklQuote($quote)) {
            /** @var \Magento\Quote\Model\Cart\ShippingMethod $shippingMethod */
            foreach ($shippingMethods as $i => $shippingMethod) {
                if ($shippingMethod->getMethodCode() != 'freeshipping') {
                    unset($shippingMethods[$i]);
                }
            }
        }

        foreach ($this->quoteHelper->getGroupedItems($quote) as $item) {
            /** @var ShippingFeeType $selectedShippingType */
            $selectedShippingType = $this->getItemSelectedShippingType($item);
            foreach ($this->getItemShippingTypes($item, $shippingAddress) as $i => $shippingType) {
                /** @var ShippingFeeType $shippingType */
                $carrierCode = 'marketplace_' . $item->getMiraklOfferId();
                $shippingMethods[] = [
                    'carrier_code'        => $carrierCode,
                    'method_code'         => $shippingType->getCode(),
                    'carrier_title'       => $item->getData('mirakl_shop_name'),
                    'method_title'        => $shippingType->getLabel(),
                    'amount'              => $shippingType->getData('total_shipping_price_incl_tax'),
                    'base_amount'         => $shippingType->getData('total_shipping_price_incl_tax'),
                    'available'           => true,
                    'price_incl_tax'      => $shippingType->getData('price_incl_tax'),
                    'price_excl_tax'      => $shippingType->getData('price_excl_tax'),
                    'offer_id'            => $item->getData('mirakl_offer_id'),
                    'title'               => ($i === 0) ? $item->getData('mirakl_shop_name') : '',
                    'selected'            => $carrierCode . '_' . $selectedShippingType->getCode(),
                    'selected_code'       => $selectedShippingType->getCode(),
                    'item_id'             => $item->getId(),
                    'shipping_type_label' => $shippingType->getLabel(),
                    'marketplace'         => true,
                ];
            }
        }
    }

    /**
     * @param   QuoteItem   $item
     * @return  ShippingFeeType
     */
    private function getItemSelectedShippingType(QuoteItem $item)
    {
        if ($shippingTypeCode = $item->getMiraklShippingType()) {
            return $this->quoteUpdater->getItemShippingTypeByCode($item, $shippingTypeCode);
        }

        return $this->quoteUpdater->getItemSelectedShippingType($item);
    }

    /**
     * @param   QuoteItem           $item
     * @param   Quote\Address|null  $shippingAddress
     * @return  ShippingFeeTypeCollection
     */
    private function getItemShippingTypes(QuoteItem $item, $shippingAddress = null)
    {
        return $this->quoteItemHelper->getItemShippingTypes($item, $shippingAddress);
    }
}
