<?php

/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Model\Quote;

use Exception;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResourceModel;
use Magento\Quote\Model\ShippingMethodManagementInterface;
use Mirakl\Connector\Helper\Config as ConnectorConfig;
use Mirakl\Connector\Model\Quote\Synchronizer as QuoteSynchronizer;
use Mirakl\FrontendDemo\Helper\Quote as QuoteHelper;
use Mirakl\FrontendDemo\Helper\Quote\Item as QuoteItemHelper;
use Mirakl\FrontendDemo\Model\Quote\Updater as QuoteUpdater;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
use Closure;
use Psr\Log\LoggerInterface;
use Retailplace\OneSellerCheckout\Api\Data\OneSellerQuoteAttributes;
use Retailplace\MultiQuote\Model\QuoteHandlers;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Retailplace\MultiQuote\Model\QuoteResource;

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
     * @var QuoteAddressResourceModel
     */
    private $quoteAddressResourceModel;

    /**
     * @var QuoteSynchronizer
     */
    private $quoteSynchronizer;

    /**
     * @var QuoteHandlers
     */
    private $quoteHandlers;

    /**
     * @var QuoteResource
     */
    private $quoteResourceModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteHelper $quoteHelper
     * @param QuoteItemHelper $quoteItemHelper
     * @param QuoteUpdater $quoteUpdater
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConnectorConfig $connectorConfig
     * @param QuoteAddressResourceModel $quoteAddressResourceModel
     * @param QuoteSynchronizer $quoteSynchronizer
     * @param QuoteHandlers $quoteHandlers
     * @param QuoteResourceModel $quoteResourceModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteHelper $quoteHelper,
        QuoteItemHelper $quoteItemHelper,
        QuoteUpdater $quoteUpdater,
        PriceCurrencyInterface $priceCurrency,
        ConnectorConfig $connectorConfig,
        QuoteAddressResourceModel $quoteAddressResourceModel,
        QuoteSynchronizer $quoteSynchronizer,
        QuoteHandlers $quoteHandlers,
        QuoteResourceModel $quoteResourceModel,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteHelper = $quoteHelper;
        $this->quoteItemHelper = $quoteItemHelper;
        $this->quoteUpdater = $quoteUpdater;
        $this->priceCurrency = $priceCurrency;
        $this->connectorConfig = $connectorConfig;
        $this->quoteAddressResourceModel = $quoteAddressResourceModel;
        $this->quoteSynchronizer = $quoteSynchronizer;
        $this->quoteHandlers = $quoteHandlers;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->logger = $logger;
    }

    /**
     * @param ShippingMethodManagementInterface $subject
     * @param Closure $proceed
     * @param string $cartId
     * @param AddressInterface $address
     * @return ShippingMethodInterface[]
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundEstimateByExtendedAddress(
        ShippingMethodManagementInterface $subject,
        Closure $proceed,
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
        if (!$this->compareAddress($shippingAddress, $data)) {
            $this->addToParentQuote($quote, $data);
        }
        $shippingAddress->addData($data);
        $this->quoteAddressResourceModel->save($shippingAddress);

        $this->quoteUpdater->synchronize($quote);

        // Retrieve default shipping methods
        $shippingMethods = $proceed($cartId, $address);

        $this->handleMiraklShippingTypes($quote, $shippingMethods, $shippingAddress);

        return $shippingMethods;
    }

    /**
     * @param ShippingMethodManagementInterface $subject
     * @param Closure $proceed
     * @param int $cartId
     * @param int $addressId
     * @return  array
     */
    public function aroundEstimateByAddressId(
        ShippingMethodManagementInterface $subject,
        Closure $proceed,
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
     * @param Quote $quote
     * @param array $shippingMethods
     * @param Quote\Address|null $shippingAddress
     */
    private function handleMiraklShippingTypes(Quote $quote, array &$shippingMethods, $shippingAddress = null)
    {
        $_totalMarketplace = [];

        // If shopping cart contains only Mirakl offers, do not show default shipping methods
        // because they are not needed. Allow only free shipping.
        if ($this->quoteHelper->isFullMiraklQuote($quote)) {
            /** @var ShippingMethod $shippingMethod */
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

            $_totalMarketplace[$item->getMiraklOfferId()] = $this->quoteItemHelper->checkExistShippingTypeByItem($item);
        }

        //check available delivery method for all offers
        if (!empty($_totalMarketplace)) {
            $isAvailableDeliveryMethodForAllOffers = true;
            foreach ($_totalMarketplace as $item) {
                if (!$item) {
                    $isAvailableDeliveryMethodForAllOffers = false;
                    break;
                }
            }

            if (!$isAvailableDeliveryMethodForAllOffers) {
                foreach ($shippingMethods as $i => $shippingMethod) {
                    if ($shippingMethod instanceof ShippingMethod) {
                        if ($shippingMethod->getMethodCode() == 'freeshipping') {
                            continue;
                        }
                    }

                    unset($shippingMethods[$i]);
                }
            }
        }
    }

    /**
     * @param QuoteItem $item
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
     * @param QuoteItem $item
     * @param Quote\Address|null $shippingAddress
     * @return  ShippingFeeTypeCollection
     */
    private function getItemShippingTypes(QuoteItem $item, $shippingAddress = null)
    {
        return $this->quoteItemHelper->getItemShippingTypes($item, $shippingAddress);
    }

    /**
     * Update Shipping Address for Parent Quote
     *
     * @param CartInterface $quote
     * @param array $data
     */
    private function addToParentQuote(CartInterface $quote, array $data)
    {
        $parentId = $quote->getData(OneSellerQuoteAttributes::QUOTE_PARENT_QUOTE_ID);
        if ($parentId) {
            $parentQuote = $this->quoteHandlers->loadQuoteById($parentId);
            $shippingAddress = $parentQuote->getShippingAddress();
            $shippingAddress->addData($data);
            try {
                $this->quoteAddressResourceModel->save($shippingAddress);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
            $this->quoteResourceModel->removeChildQuotes($parentQuote->getId(), $quote->getId());
        } else {
            $this->quoteResourceModel->removeChildQuotes($quote->getId());
        }
    }

    /**
     * Check if Address Fields were changed
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $shippingAddress
     * @param array $data
     * @return bool
     */
    private function compareAddress(AddressInterface $shippingAddress, array $data): bool
    {
        return array_intersect_key($shippingAddress->getData(), $data) == $data;
    }
}
