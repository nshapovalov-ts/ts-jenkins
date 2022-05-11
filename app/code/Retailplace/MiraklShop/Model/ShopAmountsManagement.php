<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as DataObjectFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Psr\Log\LoggerInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklShop\Api\Data\ShopAmountsInterface;
use Retailplace\MiraklShop\Api\Data\ShopAmountsInterfaceFactory;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Retailplace\ShippingEstimation\Helper\Data as ShippingEstimationHelper;
use Mirakl\Connector\Model\OfferFactory;

/**
 * Class ShopAmountsManagement
 */
class ShopAmountsManagement
{
    /** @var \Retailplace\MiraklShop\Api\Data\ShopAmountsInterfaceFactory */
    private $shopAmountsFactory;

    /** @var \Magento\Checkout\Model\Session */
    private $checkoutSession;

    /** @var \Retailplace\ShippingEstimation\Helper\Data */
    private $shippingEstimationHelper;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    private $offerFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopAmountsInterfaceFactory $shopAmountsFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Retailplace\ShippingEstimation\Helper\Data $shippingEstimationHelper
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Mirakl\Connector\Model\OfferFactory $offerFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ShopAmountsInterfaceFactory $shopAmountsFactory,
        Session $checkoutSession,
        ShippingEstimationHelper $shippingEstimationHelper,
        DataObjectFactory $dataObjectFactory,
        OfferFactory $offerFactory,
        LoggerInterface $logger
    ) {
        $this->shopAmountsFactory = $shopAmountsFactory;
        $this->checkoutSession = $checkoutSession;
        $this->shippingEstimationHelper = $shippingEstimationHelper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->offerFactory = $offerFactory;
        $this->logger = $logger;
    }

    /**
     * Calculate Shop Amounts
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterface $shop
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function calculateShopAmounts(ShopInterface $shop): ShopAmountsInterface
    {
        $quote = $this->getQuote();
        if ($quote) {
            $shopTotal = $this->getShopTotal((int) $shop->getId(), $quote);
            $shopQuotableTotal = $this->getShopQuotableTotal((int) $shop->getId(), $quote);
        } else {
            $shopTotal = 0;
            $shopQuotableTotal = 0;
        }

        /** @var \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface $shopAmounts */
        $shopAmounts = $this->shopAmountsFactory->create();
        $shopAmounts->setShopTotal($shopTotal);
        $shopAmounts->setShopQuotableTotal($shopQuotableTotal);

        $this->updateMinOrder((float) $shop->getData('min-order-amount'), $shopTotal, $shopAmounts);
        $this->updateMinQuote((float) $shop->getData('min_quote_request_amount'), $shopQuotableTotal, $shopAmounts);

        $freeShippingData = $this->dataObjectFactory->create();
        if ($shop->getFreeShipping()) {
            $freeShippingData->setAmount(0);
        } else {
            $freeShippingData = $this->shippingEstimationHelper->getFreeShippingData($shop);
        }

        $this->updateFreeShipping(
            $freeShippingData,
            $shopTotal,
            $shopAmounts
        );

        return $shopAmounts;
    }

    /**
     * Update Minimum Order Data
     *
     * @param float $minOrderAmount
     * @param float $shopTotal
     * @param \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface $shopAmounts
     */
    private function updateMinOrder(float $minOrderAmount, float $shopTotal, ShopAmountsInterface $shopAmounts)
    {
        $shopAmounts->setMinOrderAmount($minOrderAmount);
        if ($shopTotal >= $shopAmounts->getMinOrderAmount()) {
            $shopAmounts->setMinOrderAmountRemaining(0);
            $shopAmounts->setMinOrderAmountPercent(100);
        } else {
            $shopAmounts->setMinOrderAmountRemaining(
                $shopAmounts->getMinOrderAmount() - $shopTotal
            );
            if ($shopAmounts->getMinOrderAmount() && $shopTotal) {
                $shopAmounts->setMinOrderAmountPercent(
                    (int)(100 / $shopAmounts->getMinOrderAmount() * $shopTotal)
                );
            }
        }
        $shopAmounts->setIsMinOrderAmountReached(!$shopAmounts->getMinOrderAmountRemaining());
    }

    /**
     * Update Minimum Quote Request Data
     *
     * @param float $minQuoteAmount
     * @param float $shopQuotableTotal
     * @param \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface $shopAmounts
     */
    private function updateMinQuote(float $minQuoteAmount, float $shopQuotableTotal, ShopAmountsInterface $shopAmounts)
    {
        $shopAmounts->setMinQuoteAmount($minQuoteAmount);
        if ($shopQuotableTotal >= $shopAmounts->getMinQuoteAmount()) {
            $shopAmounts->setMinQuoteAmountRemaining(0);
            $shopAmounts->setMinQuoteAmountPercent(100);
        } else {
            $shopAmounts->setMinQuoteAmountRemaining(
                $shopAmounts->getMinQuoteAmount() - $shopQuotableTotal
            );
            if ($shopAmounts->getMinQuoteAmount() && $shopQuotableTotal) {
                $shopAmounts->setMinQuoteAmountPercent(
                    (int)(100 / $shopAmounts->getMinQuoteAmount() * $shopQuotableTotal)
                );
            }
        }
        $shopAmounts->setIsMinQuoteAmountReached(!$shopAmounts->getMinQuoteAmountRemaining());
    }

    /**
     * Update Free Shipping Data
     *
     * @param \Magento\Framework\DataObject|null $freeShippingData
     * @param float $shopTotal
     * @param \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface $shopAmounts
     */
    private function updateFreeShipping(?DataObject $freeShippingData, float $shopTotal, ShopAmountsInterface $shopAmounts)
    {
        if ($freeShippingData) {
            $freeShippingAmount = (float) $freeShippingData->getAmount();
            $shopAmounts->setFreeShippingAmount($freeShippingAmount);
            if ($shopTotal >= $shopAmounts->getFreeShippingAmount()) {
                $shopAmounts->setFreeShippingAmountRemaining(0);
                $shopAmounts->setFreeShippingAmountPercent(100);
            } else {
                $shopAmounts->setFreeShippingAmountRemaining(
                    $shopAmounts->getFreeShippingAmount() - $shopTotal
                );
                if ($shopAmounts->getFreeShippingAmount() && $shopTotal) {
                    $shopAmounts->setFreeShippingAmountPercent(
                        (int)(100 / $shopAmounts->getFreeShippingAmount() * $shopTotal)
                    );
                }
            }
            $shopAmounts->setIsFreeShippingAmountReached(!$shopAmounts->getFreeShippingAmountRemaining());
        }
    }

    /**
     * Get Current Customer Quote
     *
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote|null
     */
    private function getQuote(): ?CartInterface
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $quote = null;
        }

        return $quote;
    }

    /**
     * Get Total amount for Shop
     *
     * @param int $shopId
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return float
     */
    private function getShopTotal(int $shopId, CartInterface $quote): float
    {
        $shopTotal = 0;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getData('mirakl_shop_id') == $shopId) {
                $shopTotal += $item->getRowTotalInclTax();
            }
        }

        return $shopTotal;
    }

    /**
     * Calculate Total of Quotable Items for Shop
     *
     * @param int $shopId
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return float
     */
    private function getShopQuotableTotal(int $shopId, CartInterface $quote): float
    {
        $shopQuotableTotal = 0;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getData('mirakl_shop_id') == $shopId) {
                $offer = $this->getOfferByQuoteItem($item);
                if ($offer && $offer->getAllowQuoteRequests()) {
                    $shopQuotableTotal += $item->getRowTotalInclTax();
                }
            }
        }

        return $shopQuotableTotal;
    }

    /**
     * Get Offer from Quote Item
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $quoteItem
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface|null
     */
    private function getOfferByQuoteItem(CartItemInterface $quoteItem): ?OfferInterface
    {
        $offer = null;
        $option = $quoteItem->getOptionByCode('mirakl_offer');
        if ($option) {
            $offerJson = $option->getValue();
            /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
            $offer = $this->offerFactory->fromJson($offerJson);
        }

        return $offer;
    }
}
