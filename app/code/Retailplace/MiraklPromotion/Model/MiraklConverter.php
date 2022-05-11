<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

namespace Retailplace\MiraklPromotion\Model;

use Magento\Sales\Model\Order;
use Mirakl\Connector\Model\Order\Converter;
use Mirakl\MMP\Common\Domain\Collection\Order\Tax\OrderTaxAmountCollection;
use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
use Mirakl\MMP\Front\Domain\Collection\Order\Create\CreateOrderOfferCollection;
use Mirakl\MMP\Front\Domain\Order\Create\CreateOrderOffer;
use Retailplace\MiraklPromotion\Model\PromotionManagement;

/**
 * class MiraklConverter
 */
class MiraklConverter extends Converter
{
    /**
     * Create offers associated to specified order
     *
     * @param   Order   $order
     * @return  CreateOrderOfferCollection
     */
    protected function createOffers(Order $order)
    {
        $offerList = new CreateOrderOfferCollection();

        foreach ($order->getAllItems() as $item) {
            $itemPromotionDeducedAmount = $item->getData(PromotionManagement::ORDER_MIRAKL_PROMOTION_DEDUCED_AMOUNT);
            /** @var \Magento\Sales\Model\Order\Item $item */
            if (!$item->getMiraklOfferId()) {
                continue;
            }

            $offer = new CreateOrderOffer();
            $offer->setOfferId((int) $item->getMiraklOfferId())
                ->setOrderLineId((int) $item->getId())
                ->setQuantity((int) $item->getQtyOrdered())
                ->setShippingPrice((float) $item->getMiraklBaseShippingFee())
                ->setShippingTypeCode($item->getMiraklShippingType())
                ->setCurrencyIsoCode($order->getBaseCurrencyCode());

            if ($customTaxApplied = unserialize($item->getMiraklCustomTaxApplied())) {
                // Offer price excluding tax
                $offer->setPrice((float) ($item->getBaseRowTotal() - $itemPromotionDeducedAmount))
                    ->setOfferPrice((float) $item->getBasePrice());

                // Add offer and shipping tax details to offer
                foreach (['taxes', 'shipping_taxes'] as $taxType) {
                    // Group taxes by type/code
                    $taxesByCode = [];
                    if (empty($customTaxApplied[$taxType])) {
                        continue;
                    }
                    foreach ($customTaxApplied[$taxType] as $tax) {
                        if (empty($customTaxApplied[$taxType])) {
                            continue;
                        }
                        $code = $tax['type'];
                        if (!isset($taxesByCode[$code])) {
                            $taxesByCode[$code] = 0;
                        }
                        $taxesByCode[$code] += $tax['base_amount'];
                    }
                    $taxes = new OrderTaxAmountCollection();
                    foreach ($taxesByCode as $code => $amount) {
                        $tax = new OrderTaxAmount($amount, $code);
                        $taxes->add($tax);
                    }
                    if ($taxes->count()) {
                        $offer->setData($taxType, $taxes);
                    }
                }
            } else {
                if ($order->getMiraklIsOfferInclTax()) {
                    // Offer price including tax
                    $offer->setPrice((float) ($item->getBaseRowTotalInclTax() - $itemPromotionDeducedAmount))
                        ->setOfferPrice((float) $item->getBasePriceInclTax());
                } else {
                    // Offer price excluding tax
                    $offer->setPrice((float) ($item->getBaseRowTotal() - $itemPromotionDeducedAmount))
                        ->setOfferPrice((float) $item->getBasePrice());

                    $orderItemsTaxes = $this->getOrderItemsTaxes($order);
                    $taxes = new OrderTaxAmountCollection();
                    foreach ($orderItemsTaxes as $orderItemTax) {
                        if ($orderItemTax['item_id'] != $item->getId() || $orderItemTax['taxable_item_type'] != 'product') {
                            continue;
                        }
                        $tax = new OrderTaxAmount($orderItemTax['real_base_amount'], $orderItemTax['code']);
                        $taxes->add($tax);
                    }
                    if ($taxes->count()) {
                        $offer->setTaxes($taxes);
                    }
                }

                if (!$order->getMiraklIsShippingInclTax()
                    && $item->getMiraklBaseShippingTaxAmount()
                    && $offer->getShippingPrice() > 0)
                {
                    // Shipping price excluding tax
                    $shippingTaxes = new OrderTaxAmountCollection();
                    $shippingTaxApplied = unserialize($item->getMiraklShippingTaxApplied());
                    if (is_array($shippingTaxApplied)) {
                        $shippingTaxInclTax = $offer->getShippingPrice();
                        foreach ($shippingTaxApplied as $shippingTaxInfo) {
                            foreach ($shippingTaxInfo['rates'] as $rateInfo) {
                                $shippingTaxAmount = $this->priceCurrency->round(
                                    $shippingTaxInclTax * $rateInfo['percent'] / 100
                                );
                                $shippingTax = new OrderTaxAmount($shippingTaxAmount, $rateInfo['code']);
                                $shippingTaxes->add($shippingTax);
                            }
                            $shippingTaxInclTax += $offer->getShippingPrice() * $shippingTaxInfo['percent'] / 100;
                        }
                    }
                    if ($shippingTaxes->count()) {
                        $offer->setShippingTaxes($shippingTaxes);
                    }
                }
            }

            $offerList->add($offer);
        }

        return $offerList;
    }
}
