<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Model\Checkout;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollectionFactory;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollectionFactory;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingRateOfferCollection;
use Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingRateOfferCollectionFactory;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFeeFactory;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeTypeFactory;
use Mirakl\MMP\Front\Domain\Shipping\ShippingRateOfferFactory;
use Retailplace\MiraklQuote\Api\Data\MiraklQuoteAttributes;

/**
 * Class ShippingRates
 */
class ShippingRates
{
    /** @var \Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollectionFactory */
    private $orderShippingFeeCollectionFactory;

    /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingFeeTypeFactory */
    private $shippingFeeTypeFactory;

    /** @var \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingRateOfferCollectionFactory */
    private $shippingRateOfferCollectionFactory;

    /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOfferFactory */
    private $shippingRateOfferFactory;

    /** @var \Mirakl\MMP\Front\Domain\Shipping\OrderShippingFeeFactory */
    private $orderShippingFeeFactory;

    /** @var \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollectionFactory */
    private $shippingFeeTypeCollectionFactory;

    /**
     * Constructor
     *
     * @param \Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollectionFactory $orderShippingFeeCollectionFactory
     * @param \Mirakl\MMP\Front\Domain\Shipping\ShippingFeeTypeFactory $shippingFeeTypeFactory
     * @param \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingRateOfferCollectionFactory $shippingRateOfferCollectionFactory
     * @param \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOfferFactory $shippingRateOfferFactory
     * @param \Mirakl\MMP\Front\Domain\Shipping\OrderShippingFeeFactory $orderShippingFeeFactory
     * @param \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollectionFactory $shippingFeeTypeCollectionFactory
     */
    public function __construct(
        OrderShippingFeeCollectionFactory $orderShippingFeeCollectionFactory,
        ShippingFeeTypeFactory $shippingFeeTypeFactory,
        ShippingRateOfferCollectionFactory $shippingRateOfferCollectionFactory,
        ShippingRateOfferFactory $shippingRateOfferFactory,
        OrderShippingFeeFactory $orderShippingFeeFactory,
        ShippingFeeTypeCollectionFactory $shippingFeeTypeCollectionFactory
    ) {
        $this->orderShippingFeeCollectionFactory = $orderShippingFeeCollectionFactory;
        $this->shippingFeeTypeFactory = $shippingFeeTypeFactory;
        $this->shippingRateOfferCollectionFactory = $shippingRateOfferCollectionFactory;
        $this->shippingRateOfferFactory = $shippingRateOfferFactory;
        $this->orderShippingFeeFactory = $orderShippingFeeFactory;
        $this->shippingFeeTypeCollectionFactory = $shippingFeeTypeCollectionFactory;
    }

    /**
     * Use provided by Seller only Shipping Methods for Mirakl Quotes
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     */
    public function addMiraklShippingRates(CartInterface $quote)
    {
        if ($quote->getData(MiraklQuoteAttributes::MIRAKL_QUOTE_ID)) {
            /** @var \Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection $orderShippingFeeCollection */
            $orderShippingFeeCollection = $this->orderShippingFeeCollectionFactory->create();
            foreach ($quote->getAllVisibleItems() as $quoteItem) {
                $orderShippingFeeCollection->add($this->getOrderShippingFee($quoteItem));
            }
            $quote->setData('mirakl_shipping_fees', $orderShippingFeeCollection);
        }
    }

    /**
     * Get Order Shipping Fee
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return \Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee
     */
    private function getOrderShippingFee(CartItemInterface $quoteItem): OrderShippingFee
    {
        /** @var \Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee $orderShippingFee */
        $orderShippingFee = $this->orderShippingFeeFactory->create();
        $orderShippingFee->setShopId($quoteItem->getData('mirakl_shop_id'));
        $orderShippingFee->setShippingTypes($this->getShippingFeeTypeCollection($quoteItem));
        $orderShippingFee->setOffers($this->getShippingRateOfferCollection($quoteItem));

        return $orderShippingFee;
    }

    /**
     * Get Shipping Rate Offer Collection
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingRateOfferCollection
     */
    private function getShippingRateOfferCollection(CartItemInterface $quoteItem): ShippingRateOfferCollection
    {
        /** @var \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingRateOfferCollection $shippingRateOfferCollection */
        $shippingRateOfferCollection = $this->shippingRateOfferCollectionFactory->create();

        /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer $shippingRateOffer */
        $shippingRateOffer = $this->shippingRateOfferFactory->create();
        $shippingRateOffer->setId($quoteItem->getData('mirakl_offer_id'));
        $shippingRateOfferCollection->add($shippingRateOffer);

        return $shippingRateOfferCollection;
    }

    /**
     * Get Shipping Fee Type Collection
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection
     */
    private function getShippingFeeTypeCollection(CartItemInterface $quoteItem): ShippingFeeTypeCollection
    {
        /** @var \Mirakl\MMP\Front\Domain\Collection\Shipping\ShippingFeeTypeCollection $shippingFeeTypeCollection */
        $shippingFeeTypeCollection = $this->shippingFeeTypeCollectionFactory->create();

        $shippingCode = $quoteItem->getData('mirakl_shipping_type');
        $shippingLabel = ucwords($quoteItem->getData('mirakl_shipping_type')) . ' (' . $quoteItem->getName() . ')';
        $shippingPrice = $quoteItem->getData('mirakl_shipping_fee');

        /** @var \Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType $shippingFeeType */
        $shippingFeeType = $this->shippingFeeTypeFactory->create();
        $shippingFeeType->setLabel($shippingLabel);
        $shippingFeeType->setCode($shippingCode);
        $shippingFeeType->setTotalShippingPrice($shippingPrice);

        $shippingFeeTypeCollection->add($shippingFeeType);

        return $shippingFeeTypeCollection;
    }
}
