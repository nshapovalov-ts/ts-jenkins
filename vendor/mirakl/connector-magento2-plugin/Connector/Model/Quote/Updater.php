<?php
namespace Mirakl\Connector\Model\Quote;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Item\OptionFactory as CustomOptionResourceFactory;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Tax\Model\CalculationFactory as TaxCalculationFactory;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Helper\Tax as TaxHelper;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use Mirakl\MMP\Common\Domain\Shipping\ShippingType;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeError;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
use Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer;
use Psr\Log\LoggerInterface;

class Updater
{
    /**
     * @var QuoteResourceFactory
     */
    protected $quoteResourceFactory;

    /**
     * @var QuoteItemResourceFactory
     */
    protected $quoteItemResourceFactory;

    /**
     * @var CustomOptionResourceFactory
     */
    protected $customOptionResourceFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var TaxCalculationFactory
     */
    protected $taxCalculationFactory;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var OfferResourceFactory
     */
    protected $offerResourceFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OfferCollector
     */
    protected $offerCollector;

    /**
     * @var Synchronizer
     */
    protected $quoteSynchronizer;

    /**
     * @var QuoteHelper
     */
    protected $quoteHelper;

    /**
     * @var TaxHelper
     */
    protected $taxHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param   QuoteResourceFactory        $quoteResourceFactory
     * @param   QuoteItemResourceFactory    $quoteItemResourceFactory
     * @param   CustomOptionResourceFactory $customOptionResourceFactory
     * @param   PriceCurrencyInterface      $priceCurrency
     * @param   TaxCalculationFactory       $taxCalculationFactory
     * @param   EventManagerInterface       $eventManager
     * @param   OfferResourceFactory        $offerResourceFactory
     * @param   Config                      $config
     * @param   OfferCollector              $offerCollector
     * @param   Synchronizer                $quoteSynchronizer
     * @param   QuoteHelper                 $quoteHelper
     * @param   TaxHelper                   $taxHelper
     * @param   LoggerInterface             $logger
     */
    public function __construct(
        QuoteResourceFactory $quoteResourceFactory,
        QuoteItemResourceFactory $quoteItemResourceFactory,
        CustomOptionResourceFactory $customOptionResourceFactory,
        PriceCurrencyInterface $priceCurrency,
        TaxCalculationFactory $taxCalculationFactory,
        EventManagerInterface $eventManager,
        OfferResourceFactory $offerResourceFactory,
        Config $config,
        OfferCollector $offerCollector,
        Synchronizer $quoteSynchronizer,
        QuoteHelper $quoteHelper,
        TaxHelper $taxHelper,
        LoggerInterface $logger
    ) {
        $this->quoteResourceFactory = $quoteResourceFactory;
        $this->quoteItemResourceFactory = $quoteItemResourceFactory;
        $this->customOptionResourceFactory = $customOptionResourceFactory;
        $this->priceCurrency = $priceCurrency;
        $this->taxCalculationFactory = $taxCalculationFactory;
        $this->eventManager = $eventManager;
        $this->offerResourceFactory = $offerResourceFactory;
        $this->config = $config;
        $this->offerCollector = $offerCollector;
        $this->quoteSynchronizer = $quoteSynchronizer;
        $this->quoteHelper = $quoteHelper;
        $this->taxHelper = $taxHelper;
        $this->logger = $logger;
    }

    /**
     * Returns selected shipping fee type
     *
     * @param   CartItemInterface   $item
     * @return  ShippingFeeType
     */
    public function getItemSelectedShippingType(CartItemInterface $item)
    {
        $orderShippingFee = $this->getItemOrderShippingFee($item);
        if ($orderShippingFee && ($selectedShippingType = $orderShippingFee->getSelectedShippingType())) {
            $shippingType = $this->getItemShippingTypeByCode($item, $selectedShippingType->getCode());
            if ($shippingType->getCode()) {
                return $shippingType;
            }
        }

        return new ShippingFeeType();
    }

    /**
     * Returns shipping fee offer of given item (item that is a Mirakl offer)
     *
     * @param   CartItemInterface   $item
     * @return  ShippingRateOffer
     */
    public function getItemShippingRateOffer(CartItemInterface $item)
    {
        $orderShippingFee = $this->getItemOrderShippingFee($item);
        if ($offers = $orderShippingFee->getOffers()) {
            foreach ($offers as $shippingRateOffer) {
                /** @var ShippingRateOffer $shippingRateOffer */
                if ($item->getMiraklOfferId() == $shippingRateOffer->getId()) {
                    return $shippingRateOffer;
                }
            }
        }

        return new ShippingRateOffer();
    }

    /**
     * Returns shipping fee type by code
     *
     * @param   CartItemInterface   $item
     * @param   string              $shippingTypeCode
     * @return  ShippingFeeType
     */
    public function getItemShippingTypeByCode(CartItemInterface $item, $shippingTypeCode)
    {
        if ($shippingTypeCode) {
            foreach ($this->getItemShippingTypes($item) as $shippingFeeType) {
                if ($shippingTypeCode == $shippingFeeType->getCode()) {
                    return $shippingFeeType;
                }
            }
        }

        return new ShippingFeeType();
    }

    /**
     * Returns available shipping types for given quote item
     *
     * @param   CartItemInterface       $item
     * @param   AddressInterface|null   $shippingAddress
     * @return  ShippingFeeTypeCollection
     */
    public function getItemShippingTypes(CartItemInterface $item, $shippingAddress = null)
    {
        if (null === $shippingAddress) {
            $shippingAddress = $item->getQuote()->getShippingAddress();
        }

        $orderShippingFee = $this->getItemOrderShippingFee($item);
        if ($shippingTypes = $orderShippingFee->getShippingTypes()) {
            /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType $shippingType */
            foreach ($shippingTypes as $shippingType) {
                if ($item->getQuote()->getMiraklIsShippingInclTax()) {
                    // Shipping prices INCLUDING tax
                    $shippingPriceInclTax = $shippingType->getTotalShippingPrice();
                    $shippingPriceExclTax = $this->taxHelper->getShippingPriceExclTax($shippingPriceInclTax, $shippingAddress);
                    $shippingType->setTotalShippingPrice($this->priceCurrency->convert($shippingPriceInclTax));
                } else {
                    // Shipping prices EXCLUDING tax
                    $shippingPriceExclTax = $shippingType->getTotalShippingPrice();
                    $shippingPriceInclTax = $this->taxHelper->getShippingPriceInclTax($shippingPriceExclTax, $shippingAddress);
                    $shippingType->setTotalShippingPrice($this->priceCurrency->convert($shippingPriceExclTax));
                }

                $shippingType->setData('item_id', $item->getId());
                $shippingType->setData('price_incl_tax', $this->priceCurrency->convert($shippingPriceInclTax));
                $shippingType->setData('price_excl_tax', $this->priceCurrency->convert($shippingPriceExclTax));
                $shippingType->setData('total_shipping_price_incl_tax', $this->priceCurrency->convert($shippingPriceInclTax));
            }

            return $shippingTypes;
        }

        return new ShippingFeeTypeCollection();
    }

    /**
     * Returns order shipping fee of given quote item (that is linked to a Mirakl offer)
     *
     * @param   CartItemInterface   $item
     * @return  OrderShippingFee
     */
    public function getItemOrderShippingFee(CartItemInterface $item)
    {
        if ($offerId = $item->getMiraklOfferId()) {
            $shippingFees = $this->quoteSynchronizer->getShippingFees($item->getQuote());
            if ($shippingFees) {
                /** @var OrderShippingFee $orderShippingFee */
                foreach ($shippingFees as $orderShippingFee) {
                    if ($offers = $orderShippingFee->getOffers()) {
                        foreach ($offers as $shippingRateOffer) {
                            /** @var ShippingRateOffer $shippingRateOffer */
                            if ($offerId == $shippingRateOffer->getId()) {
                                return $orderShippingFee;
                            }
                        }
                    }
                }
            }
        }

        return new OrderShippingFee();
    }

    /**
     * Prepares custom Mirakl taxes before being saved into quote item
     *
     * @param   CartItemInterface   $item
     * @param   ShippingRateOffer   $shippingRateOffer
     * @return  array
     */
    protected function prepareCustomTaxes(CartItemInterface $item, ShippingRateOffer $shippingRateOffer)
    {
        $customTaxes = [];

        if (($shippingRateOffer->getShippingTaxes() && $shippingRateOffer->getShippingTaxes()->count()) ||
            ($shippingRateOffer->getTaxes() && $shippingRateOffer->getTaxes()->count())
        ) {
            $offerTaxes = $shippingRateOffer->getTaxes()->toArray();
            $shippingTaxes = $shippingRateOffer->getShippingTaxes()->toArray();
            foreach ($offerTaxes as $i => $tax) {
                $offerTaxes[$i]['base_amount'] = (float) $tax['amount'];
                $offerTaxes[$i]['amount'] = $this->priceCurrency->convert($tax['amount'], $item->getStore());
            }
            foreach ($shippingTaxes as $i => $tax) {
                $shippingTaxes[$i]['base_amount'] = (float) $tax['amount'];
                $shippingTaxes[$i]['amount'] = $this->priceCurrency->convert($tax['amount'], $item->getStore());
            }
            $customTaxes = [
                'product_tax_code' => $shippingRateOffer->getProductTaxCode(),
                'shipping_taxes'   => $shippingTaxes,
                'taxes'            => $offerTaxes,
            ];
        }

        return $customTaxes;
    }

    /**
     * Reset all quote shipping types
     *
     * @param   CartInterface   $quote
     * @return  $this
     */
    public function resetQuoteShippingTypes(CartInterface $quote)
    {
        foreach ($this->offerCollector->getQuoteItems($quote) as $item) {
            if ($item->getMiraklOfferId()) {
                $this->resetItemShippingType($item);
            }
        }

        return $this;
    }

    /**
     * Reset quote item shipping type
     *
     * @param   CartItemInterface   $item
     * @return  $this
     */
    public function resetItemShippingType(CartItemInterface $item)
    {
        $this->setItemShippingType($item, new ShippingFeeType());

        return $this;
    }

    /**
     * Update quote item shipping fee information
     *
     * @param   CartItemInterface   $item
     * @param   ShippingType        $shippingType
     * @return  $this
     */
    public function setItemShippingType(CartItemInterface $item, ShippingType $shippingType)
    {
        $item->setMiraklShippingType($shippingType->getCode());
        $item->setMiraklShippingTypeLabel($shippingType->getLabel());

        return $this;
    }

    /**
     * Update quote item shipping type information
     *
     * @param   CartItemInterface   $item
     * @param   string              $shippingTypeCode
     * @return  $this
     */
    public function setItemShippingTypeByCode(CartItemInterface $item, $shippingTypeCode)
    {
        // Set shipping type information on specified item
        $this->setItemShippingType($item, $this->getItemShippingTypeByCode($item, $shippingTypeCode));

        return $this;
    }

    /**
     * Update quote item shipping fee amount
     *
     * @param   CartItemInterface   $item
     * @param   ShippingRateOffer   $shippingRateOffer
     * @return  $this
     */
    public function setItemShippingFee(CartItemInterface $item, ShippingRateOffer $shippingRateOffer)
    {
        $baseShippingFee = $shippingRateOffer->getLineShippingPrice();
        $item->setMiraklBaseShippingFee($baseShippingFee);

        $convertedShippingFee = $this->priceCurrency->convert($baseShippingFee, $item->getStore());
        $item->setMiraklShippingFee($convertedShippingFee);

        $item->unsMiraklCustomTaxApplied();
        $customTaxApplied = $this->prepareCustomTaxes($item, $shippingRateOffer);

        if (!empty($customTaxApplied)) {
            // If Mirakl custom taxes are available, store them
            $item->setMiraklCustomTaxApplied(serialize($customTaxApplied));
            if (isset($customTaxApplied['shipping_taxes'])) {
                $shippingTaxAmount = 0;
                $shippingTaxBaseAmount = 0;
                foreach ($customTaxApplied['shipping_taxes'] as $shippingTax) {
                    $shippingTaxAmount += $shippingTax['amount'];
                    $shippingTaxBaseAmount += $shippingTax['base_amount'];
                }
                $item->setMiraklCustomShippingTaxAmount($shippingTaxAmount);
                $item->setMiraklBaseCustomShippingTaxAmount($shippingTaxBaseAmount);
            }

            // Reset potential previous taxes info
            $item->setMiraklShippingTaxPercent(null);
            $item->setMiraklBaseShippingTaxAmount(null);
            $item->setMiraklShippingTaxAmount(null);
            $item->setMiraklShippingTaxApplied(null);
        } else {
            /** @var CartInterface $quote */
            $quote = $item->getQuote();
            /* @var \Magento\Tax\Model\Calculation $calculator */
            $calculator = $this->taxCalculationFactory->create();
            $request = $calculator->getRateRequest(
                $quote->getShippingAddress(),
                $quote->getBillingAddress(),
                $quote->getCustomerTaxClassId(),
                $item->getStore()
            );
            $shippingTaxClassId = $this->config->getShippingTaxClass($item->getStoreId());
            $request->setProductClassId($shippingTaxClassId);
            $rate = $calculator->getRate($request);
            $item->setMiraklShippingTaxPercent($rate);
            $taxApplied = $calculator->getAppliedRates($request);

            if (!$quote->getMiraklIsShippingInclTax()) {
                // Shipping price is excluding tax
                $miraklShippingTaxAmount = $convertedShippingFee * $rate / 100;
                $miraklBaseShippingTaxAmount = $baseShippingFee * $rate / 100;
                foreach ($taxApplied as $i => $taxInfo) {
                    $taxApplied[$i]['amount'] = $convertedShippingFee * $taxInfo['percent'] / 100;
                    $taxApplied[$i]['base_amount'] = $baseShippingFee * $taxInfo['percent'] / 100;
                }
            } else {
                // Shipping price is including tax
                $miraklShippingTaxAmount = $convertedShippingFee - ($convertedShippingFee / (1 + $rate / 100));
                $miraklBaseShippingTaxAmount = $baseShippingFee - ($baseShippingFee / (1 + $rate / 100));
                foreach ($taxApplied as $i => $taxInfo) {
                    $taxApplied[$i]['amount'] = ($convertedShippingFee - $miraklShippingTaxAmount) * $taxInfo['percent'] / 100;
                    $taxApplied[$i]['base_amount'] = ($baseShippingFee - $miraklBaseShippingTaxAmount) * $taxInfo['percent'] / 100;
                }
            }

            $item->setMiraklShippingTaxAmount($miraklShippingTaxAmount);
            $item->setMiraklBaseShippingTaxAmount($miraklBaseShippingTaxAmount);
            $item->setMiraklShippingTaxApplied(serialize($taxApplied));
        }

        return $this;
    }

    /**
     * Update quote item shop information
     *
     * @param   CartItemInterface   $item
     * @param   Offer               $offer
     * @return  $this
     */
    public function setShopToItem(CartItemInterface $item, Offer $offer)
    {
        $item->setMiraklShopId($offer->getShopId());
        $item->setMiraklShopName($offer->getShopName());

        return $this;
    }

    /**
     * Verify that offers in cart are still valid and synchronize them if needed (quantity, price, ...)
     *
     * @param   CartInterface   $quote
     */
    public function synchronize(CartInterface $quote)
    {
        if (!$quote->getItemsCount() || !$this->quoteHelper->isMiraklQuote($quote)) {
            $quote->setMiraklShippingZone(null)
                ->setMiraklBaseShippingFee(null)
                ->setMiraklShippingFee(null)
                ->setMiraklBaseShippingTaxAmount(null)
                ->setMiraklShippingTaxAmount(null)
                ->setMiraklBaseCustomShippingTaxAmount(null)
                ->setMiraklCustomShippingTaxAmount(null);
            $this->quoteResourceFactory->create()->save($quote);

            return;
        }

        $hasError = false;

        $offerResource = $this->offerResourceFactory->create();

        $addQuoteError = function ($message) use (&$quote) {
            $quote->setHasError(true);
            $quote->addMessage($message);
        };

        try {
            $shippingFees = $this->quoteSynchronizer->getShippingFees($quote);

            $this->eventManager->dispatch('mirakl_check_quote_offers_before', [
                'quote' => $quote,
                'shipping_fees' => $shippingFees,
            ]);

            // Useful closure to mark given item as failed
            $addItemError = function (CartItemInterface $item, $message) use (&$hasError) {
                $hasError = true;
                $item->setHasError(true);
                $item->removeMessageByText($message); // Avoid duplicate messages
                $item->addMessage($message);
            };

            // Update offer data on quote item if it has changed
            $updateItemOffer = function (CartItemInterface $item, Offer $offer) use ($offerResource) {
                $offerResource->load($offer, $offer->getId()); // reload offer data
                /** @var \Magento\Quote\Model\Quote\Item\Option $customOption */
                $customOption = $item->getProduct()->getCustomOption('mirakl_offer');
                if ($customOption && $customOption->getId()) {
                    $customOption->setValue($offer->toJson());
                    $this->customOptionResourceFactory->create()->save($customOption);
                }
            };

            /** @var CartItemInterface|QuoteItem $item */
            foreach ($this->offerCollector->getItemsWithOffer($quote) as $item) {
                /** @var Offer $offer */
                $offer = $item->getData('offer');
                $offerId = $offer->getId();

                $item->setMiraklOfferId($offerId);
                $this->setShopToItem($item, $offer);

                // Check if offer is still present in Mirakl platform
                if ($shippingFees && $shippingFees->getErrors()) {
                    /** @var ShippingFeeError $error */
                    foreach ($shippingFees->getErrors() as $error) {
                        if ($error->getOfferId() != $offerId) {
                            continue; // no error on this offer
                        }
                        if ($error->getErrorCode() == 'OFFER_NOT_FOUND') {
                            if ($this->config->isAutoRemoveOffers()) {
                                $offerResource->delete($offer);
                                $message = __(
                                    'Offer for product "%1" is not available anymore. It has been removed from your cart.',
                                    $item->getName()
                                );
                                $addQuoteError($message);
                                $quote->removeItem($item->getId());
                            } else {
                                $addItemError($item, __('This offer no longer exists.'));
                            }
                        } elseif ($error->getErrorCode() == 'SHIPPING_TYPE_NOT_ALLOWED' && $error->getShippingTypeCode()) {
                            // Message on item are displed only on cart view so modifications need to be made in cart controler
                            $item->setMiraklShippingType('');
                            $item->setMiraklShippingTypeLabel('');
                            $addItemError($item, __(
                                'The selected shipping type is not allowed for this offer, it has been reset. ' .
                                'Please refresh the page to list available shipping types.'
                            ));
                        } else {
                            $addItemError($item, __(
                                'An error occurred with this offer: %1. Try to modify shipping address.',
                                __($error->getErrorCode())
                            ));
                        }
                        continue 2;
                    }
                }

                $orderShippingFee = $this->getItemOrderShippingFee($item);

                $shippingRateOffer = $this->getItemShippingRateOffer($item);
                if (!$shippingRateOffer->getId()) {
                    $addItemError($item, __('This offer is not available.'));
                    continue;
                }

                // Update quote item shipping type and fee
                $this->setItemShippingFee($item, $shippingRateOffer);
                $this->setItemShippingType($item, $orderShippingFee->getSelectedShippingType());
                $item->setMiraklLeadtimeToShip($orderShippingFee->getLeadtimeToShip());

                // Check if offer quantity has changed
                if ($offer->getQty() != $shippingRateOffer->getQuantity()) {
                    // Message on item are displed only on cart view so modifications need to be made in cart controler
                    if ($this->config->isAutoUpdateOffers()) {
                        $offerResource->updateOrderConditions($offerId);
                        $updateItemOffer($item, $offer);
                    }
                }

                // Check if requested quantity is available
                if ($shippingRateOffer->getLineOriginalQuantity() != $shippingRateOffer->getLineQuantity()) {
                    if ($item->getQty() > $shippingRateOffer->getQuantity()) {
                        $addItemError($item, __(
                            'Requested quantity of %1 is not available for product "%2". Quantity available: %3.',
                            (int) $item->getQty(),
                            $item->getName(),
                            (int) $shippingRateOffer->getQuantity()
                        ));
                    } else {
                        // Problem with order conditions
                        /** @var \Mirakl\MMP\FrontOperator\Domain\Offer $sdkOffer */
                        $sdkOffer = $offerResource->updateOrderConditions($offerId);
                        $updateItemOffer($item, $offer);

                        if ($sdkOffer->getMinOrderQuantity()
                                && $shippingRateOffer->getLineOriginalQuantity() < $sdkOffer->getMinOrderQuantity()) {
                            $addItemError($item, __(
                                'The fewest you may purchase is %1.',
                                $sdkOffer->getMinOrderQuantity() * 1
                            ));
                        } elseif ($sdkOffer->getMaxOrderQuantity()
                                && $shippingRateOffer->getLineOriginalQuantity() > $sdkOffer->getMaxOrderQuantity()) {
                            $addItemError($item, __(
                                'The most you may purchase is %1.',
                                $sdkOffer->getMaxOrderQuantity() * 1
                            ));
                        } elseif ($sdkOffer->getPackageQuantity() > 1
                                && $shippingRateOffer->getLineOriginalQuantity() % $sdkOffer->getPackageQuantity() != 0) {
                            $addItemError($item, __(
                                'You can buy this product only in quantities of %1 at a time.',
                                $sdkOffer->getPackageQuantity()
                            ));
                        }
                    }

                    $addItemError($item, __(
                        'Quantity was recalculated from %1 to %2',
                        $item->getQty(),
                        $shippingRateOffer->getLineQuantity()
                    ));
                    $item->setQty($shippingRateOffer->getLineQuantity());
                }

                // Check if price has changed
                $offerPrice = $shippingRateOffer->getPrice();
                $itemPrice = $this->config->getOffersIncludeTax($quote->getStoreId())
                    ? $item->getPriceInclTax()
                    : $item->getPrice();
                if ($itemPrice != $offerPrice) {
                    $item->addMessage(__(
                        'Price has changed from %1 to %2',
                        $this->priceCurrency->format($itemPrice, false),
                        $this->priceCurrency->format($offerPrice, false)
                    ));
                    $updateItemOffer($item, $offer);
                }

                $this->quoteItemResourceFactory->create()->save($item);
            }

            // Mark quote as failed if an error occurred
            if ($hasError) {
                $addQuoteError(
                    __('Some errors occurred while processing your shopping cart. Please verify it.')
                );
            }

            $quote->collectTotals();
            $this->quoteResourceFactory->create()->save($quote);

            $this->eventManager->dispatch('mirakl_check_quote_offers_after', [
                'quote' => $quote,
                'shipping_fees' => $shippingFees,
            ]);
        } catch (\Exception $e) {
            $addQuoteError(__('An error occurred while processing your shopping cart.' .
                ' Please contact store owner if the problem persists.'));
            $this->logger->critical($e->getTraceAsString());
        }
    }

    /**
     * @param   array           $offersShippingTypes
     * @param   CartInterface   $quote
     * @param   bool            $resetAll
     * @param   bool            $saveItem
     * @return  $this
     */
    public function updateOffersShippingTypes(array $offersShippingTypes, CartInterface $quote = null, $resetAll = true, $saveItem = false)
    {
        if ($resetAll) {
            // Reset all Mirakl items shipping types
            $this->resetQuoteShippingTypes($quote);
        }

        $quoteItemResource = $this->quoteItemResourceFactory->create();

        // Update shipping information on quote items
        $groupedOfferIds = $this->quoteSynchronizer->getGroupedOfferIds($quote);
        $itemsWithOffer = $this->offerCollector->getItemsWithOffer($quote);
        foreach ($offersShippingTypes as $offerId => $shippingTypeCode) {
            foreach ($groupedOfferIds as $offerIds) {
                if (in_array($offerId, $offerIds)) {
                    $items = array_intersect_key($itemsWithOffer, array_flip($offerIds));
                    foreach ($items as $item) {
                        $this->setItemShippingTypeByCode($item, $shippingTypeCode);
                        if ($saveItem) {
                            $quoteItemResource->save($item);
                        }
                    }
                }
            }
        }

        // Synchronize shipping information
        $this->quoteSynchronizer->syncQuoteShippingInfo($quote);

        // Update shipping fees
        foreach ($itemsWithOffer as $item) {
            $shippingRateOffer = $this->getItemShippingRateOffer($item);
            $this->setItemShippingFee($item, $shippingRateOffer);
            if ($saveItem) {
                $quoteItemResource->save($item);
            }
        }

        return $this;
    }
}
