<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\MiraklConnector\Plugin\Model\Quote;

use Exception;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option;
use Magento\Quote\Model\ResourceModel\Quote\Item\OptionFactory as CustomOptionResourceFactory;
use Magento\Quote\Model\ResourceModel\Quote\ItemFactory as QuoteItemResourceFactory;
use Magento\Quote\Model\ResourceModel\QuoteFactory as QuoteResourceFactory;
use Magento\Tax\Model\CalculationFactory as TaxCalculationFactory;
use Mirakl\Connector\Helper\Config;
use Mirakl\Connector\Helper\Quote as QuoteHelper;
use Mirakl\Connector\Helper\Tax as TaxHelper;
use Mirakl\Connector\Model\Offer;
use Mirakl\Connector\Model\Quote\OfferCollector;
use Mirakl\Connector\Model\Quote\Synchronizer;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;
use Mirakl\Connector\Model\ResourceModel\OfferFactory as OfferResourceFactory;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeError;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklPromotion\Model\PromotionManagement;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;

/**
 * Updater Constructor
 */
class Updater extends QuoteUpdater
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
     * @var PromotionManagement
     */
    protected $promotionManagement;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string[][]
     */
    const AVAILABLE_REGION = [
                'NSW' => ['2000-2599', '2619-2899', '2921-2999'],
                'ACT' => ['2600-2618', '2900-2920'],
                'VIC' => ['3000-3999'],
                'QLD' => ['4000-4999'],
                'SA' => ['5000-5799'],
                'WA' => ['6000-6797', '6800-6999'],
                'TAS' => ['7000-7799', '7800-7999'],
                'NT' => ['0800-0899']
            ];

    /**
     * Updater Constructor
     *
     * @param \Magento\Quote\Model\ResourceModel\QuoteFactory $quoteResourceFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\ItemFactory $quoteItemResourceFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\OptionFactory $customOptionResourceFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Tax\Model\CalculationFactory $taxCalculationFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Mirakl\Connector\Model\ResourceModel\OfferFactory $offerResourceFactory
     * @param \Mirakl\Connector\Helper\Config $config
     * @param \Mirakl\Connector\Model\Quote\OfferCollector $offerCollector
     * @param \Mirakl\Connector\Model\Quote\Synchronizer $quoteSynchronizer
     * @param \Mirakl\Connector\Helper\Quote $quoteHelper
     * @param \Mirakl\Connector\Helper\Tax $taxHelper
     * @param \Retailplace\MiraklPromotion\Model\PromotionManagement $promotionManagement
     * @param \Psr\Log\LoggerInterface $logger
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
        PromotionManagement $promotionManagement,
        LoggerInterface $logger
    ) {
        parent::__construct($quoteResourceFactory, $quoteItemResourceFactory, $customOptionResourceFactory,
            $priceCurrency, $taxCalculationFactory, $eventManager, $offerResourceFactory, $config, $offerCollector,
            $quoteSynchronizer, $quoteHelper, $taxHelper, $logger);

        $this->promotionManagement = $promotionManagement;
    }

    /**
     * Verify that offers in cart are still valid and synchronize them if needed (quantity, price, ...)
     *
     * @param QuoteUpdater $subject
     * @param callable $proceed
     * @param CartInterface $quote
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function aroundSynchronize(QuoteUpdater $subject, callable $proceed, CartInterface $quote)
    {
        if ($quote->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)) {
            return;
        }

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
        };

        try {

            $address = $quote->getShippingAddress() ? $quote->getShippingAddress() : $quote->getBillingAddress();
            $customerPostcode = (int)$address->getData('postcode');
            $found = false;

            foreach (self::AVAILABLE_REGION as $region => $ranges) {
                foreach ($ranges as $range) {
                    $listNumbers = explode("-", $range);
                    if (isset($listNumbers[0]) && isset($listNumbers[1])) {
                        if (($customerPostcode >= (int)$listNumbers[0])
                            && ($customerPostcode <= (int)$listNumbers[1])) {
                            $found = true;
                            break;
                        }
                    }
                }
                if ($found) {
                    break;
                }
            }

            if (!$found) {
                $quote->setHasError(true);
                $quote->addMessage(__('Please enter postcode in Australia, contact our support team for further assistance.'));
            }
            $shippingFees = $this->quoteSynchronizer->getShippingFees($quote);

            $this->eventManager->dispatch('mirakl_check_quote_offers_before', [
                'quote' => $quote,
                'shipping_fees' => $shippingFees,
            ]);

            // Useful closure to mark given item as failed
            $addItemError = function (CartItemInterface $item, $message) use (&$hasError, &$customerPostcode, &$found, $quote) {
                $hasError = true;
                $item->setHasError(true);
                $item->removeMessageByText($message); // Avoid duplicate messages
                $item->addMessage($message);
                $logMessage = $message . ' customerId: ' . $quote->getCustomerId();
                $offer = $item->getData('offer');
                if (is_object($offer)) {
                    $logMessage.= ' offerId: ' . $offer->getId();
                }
                $this->logger->warning($logMessage);
            };

            // Update offer data on quote item if it has changed
            $updateItemOffer = function (CartItemInterface $item, Offer $offer) use ($offerResource) {
                $offerResource->load($offer, $offer->getId()); // reload offer data
                /** @var Option $customOption */
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
                            (int)$item->getQty(),
                            $item->getName(),
                            (int)$shippingRateOffer->getQuantity()
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
                    if ($itemPrice > $offerPrice) {
                        $item->addMessage(__(
                            'Price has changed from %1 to %2',
                            $this->priceCurrency->format($itemPrice, false),
                            $this->priceCurrency->format($offerPrice, false)
                        ));
                    }

                    $updateItemOffer($item, $offer);
                }

                $this->quoteItemResourceFactory->create()->save($item);
            }

            // Mark quote as failed if an error occurred
            if ($shippingFees) {
                /** @var \Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee $item */
                $quoteItemIds = [];
                foreach ($shippingFees->getItems() as $item) {
                    $quoteItemIds = array_merge(
                        $quoteItemIds,
                        $this->promotionManagement->addMiraklPromotionsToQuote($quote, $item)
                    );
                }
                $this->promotionManagement->removePromotionsFromQuote($quoteItemIds, $quote);

                foreach ($shippingFees->getErrors() as $error) {
                    if ($error->getErrorCode() == 'SHIPPING_ZONE_NOT_ALLOWED' && $hasError) {
                        $addQuoteError(
                            __('Please enter postcode in Australia, contact our support team for further assistance.')
                        );
                    }
                }
            }
            if ($hasError) {
                $addQuoteError(
                    __('Some errors occurred while processing your shopping cart. Please verify it. TEST') //todo
                );
            }

            $quote->collectTotals();
            $this->quoteResourceFactory->create()->save($quote);

            $this->eventManager->dispatch('mirakl_check_quote_offers_after', [
                'quote' => $quote,
                'shipping_fees' => $shippingFees,
            ]);
        } catch (Exception $e) {
            $addQuoteError(__('An error occurred while processing your shopping cart.' .
                ' Please contact store owner if the problem persists.'));
            $this->logger->critical($e->getTraceAsString());
        }
    }
}
