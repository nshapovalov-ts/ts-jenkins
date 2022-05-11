<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Plugin\Helper;

use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use Mirakl\MMP\Front\Domain\Collection\Shipping\OrderShippingFeeCollection;
use Mirakl\MMP\Front\Domain\Shipping\OrderShippingFee;
use Mirakl\MMP\Front\Domain\Shipping\ShippingFeeType;
use Mirakl\MMP\Front\Domain\Shipping\ShippingRateOffer;
use Retailplace\MiraklFrontendDemo\Helper\Data as Helper;

class Shipping
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var ShopCollectionFactory
     */
    protected $shopCollectionFactory;

    /**
     * @param Helper $helper
     * @param ShopCollectionFactory $shopCollectionFactory
     */
    public function __construct(
        Helper $helper,
        ShopCollectionFactory $shopCollectionFactory
    ) {
        $this->helper = $helper;
        $this->shopCollectionFactory = $shopCollectionFactory;
    }

    /**
     * @param \Mirakl\Api\Helper\Shipping $subject
     * @param OrderShippingFeeCollection $result
     * @return OrderShippingFeeCollection
     */
    public function afterGetShippingFees(
        \Mirakl\Api\Helper\Shipping $subject,
        OrderShippingFeeCollection $result,
        $zone
    ): OrderShippingFeeCollection {
        if (!$result || !$result->count()) {
            return $result;
        }

        $fixedShippingFeePercent = $this->helper->getShippingFeePercent();
        $shopIds = [];

        /** @var OrderShippingFee $orderShippingFee */
        foreach ($result as $orderShippingFee) {
            if ($orderShippingFee->getOffers()) {
                $shopIds[] = $orderShippingFee->getShopId();
            }
        }

        /** @var ShopCollection $shopCollection */
        $shopCollection = $this->shopCollectionFactory->create();
        $shopCollection->addFieldToFilter('id', ['in' => $shopIds]);

        $notFixedPercentageShippingRules = [];
        foreach ($shopCollection as $shop) {
            if (!$additionalInfo = $shop->getAdditionalInfo()) {
                continue;
            }

            $shippingInfo = $additionalInfo->getData('shipping_info');
            if (empty($shippingInfo['shipping_rules'])) {
                continue;
            }

            foreach ($shippingInfo['shipping_rules'] as $shippingInfoItem) {
                if (empty($shippingInfoItem['additional_fields'])) {
                    continue;
                }

                if (empty($shippingInfoItem['shipping_type']['code'])
                    || empty($shippingInfoItem['shipping_zone']['code'])) {
                    continue;
                }

                $additionalFields = $shippingInfoItem['additional_fields'];
                $shippingType = $shippingInfoItem['shipping_type']['code'];
                $shippingZone = $shippingInfoItem['shipping_zone']['code'];

                foreach ($additionalFields as $additionalField) {
                    if (!empty($additionalField['code']) && $additionalField['code'] === "percent" && !empty($additionalField['value'])) {
                        $notFixedPercentageShippingRules[$shop->getId()][$shippingZone][$shippingType] =
                            (float) $additionalField['value'];
                    }
                }
            }
        }

        /** @var OrderShippingFee $orderShippingFee */
        foreach ($result as $orderShippingFee) {
            if ($offers = $orderShippingFee->getOffers()) {
                $shop = $shopCollection->getItemById($orderShippingFee->getShopId());

                $isNotFixedPercentage = false;
                $shippingFeePercent = null;

                if ($shop && $shop->getIsFixedPercentShipping()) {
                    $shippingFeePercent = $fixedShippingFeePercent;
                }

                if (!empty($notFixedPercentageShippingRules[$orderShippingFee->getShopId()][$zone])) {
                    $isNotFixedPercentage = true;
                }

                if (empty($shippingFeePercent) && !$isNotFixedPercentage) {
                    continue;
                }

                $selectedShippingTypeCode = $orderShippingFee->getSelectedShippingType()->getCode();

                /** @var ShippingFeeType $shippingType */
                foreach ($orderShippingFee->getShippingTypes() as $shippingType) {
                    if ($shippingType->getTotalShippingPrice() == 0) {
                        continue;
                    }

                    if ($isNotFixedPercentage) {
                        $shippingTypeCode = $shippingType->getCode();

                        if(empty($notFixedPercentageShippingRules[$orderShippingFee->getShopId()][$zone][$shippingTypeCode])){
                            continue;
                        }

                        $shippingFeePercent = $notFixedPercentageShippingRules[$orderShippingFee->getShopId()][$zone]
                        [$shippingTypeCode];
                    }

                    if (empty($shippingFeePercent)) {
                        continue;
                    }

                    $totalShippingPrice = 0;
                    /** @var ShippingRateOffer $shippingRateOffer */
                    foreach ($offers as $shippingRateOffer) {
                        $linePrice = $shippingRateOffer->getLinePrice();
                        $lineShippingPrice = $linePrice / 100 * $shippingFeePercent;
                        $totalShippingPrice += $lineShippingPrice;

                        if ($shippingType->getCode() == $selectedShippingTypeCode) {
                            $shippingRateOffer->setLineShippingPrice($lineShippingPrice);
                            $shippingRateOffer->setLineTotalPrice($linePrice + $lineShippingPrice);
                        }
                    }
                    $shippingType->setTotalShippingPrice($totalShippingPrice);
                }
            }
        }

        return $result;
    }
}
