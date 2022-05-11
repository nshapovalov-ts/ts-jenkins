<?php
declare(strict_types=1);

/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\Stripe\Rewrite\Model\Stripe;

use StripeIntegration\Payments\Model\Stripe\InvoiceItem as StripeInvoiceItem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order;
use StripeIntegration\Payments\Model\Config;
use StripeIntegration\Payments\Helper\Generic;
use StripeIntegration\Payments\Helper\Rollback;
use StripeIntegration\Payments\Model\Stripe\CouponFactory;

/**
 * Class InvoiceItem
 */
class InvoiceItem extends StripeInvoiceItem
{
    /**
     * @var string
     */
    protected $objectSpace = 'invoiceItems';

    /**
     * @var string
     */
    private $taxRateId;

    /**
     * @var CouponFactory
     */
    private $couponFactory;

    /**
     * @var null
     */
    protected $object = null;

    /**
     * @param Config $config
     * @param Generic $helper
     * @param Rollback $rollback
     * @param CouponFactory $couponFactory
     */
    public function __construct(
        Config $config,
        Generic $helper,
        Rollback $rollback,
        CouponFactory $couponFactory
    ) {
        parent::__construct($config, $helper, $rollback);
        $this->couponFactory = $couponFactory;
    }

    /**
     * From Order Item
     *
     * @param Item $item
     * @param Order $order
     * @param string $customerId
     * @return $this|InvoiceItem
     * @throws LocalizedException
     */
    public function fromOrderItem($item, $order, $customerId): InvoiceItem
    {
        $taxRates = [];
        $productTaxRate = $this->getProductTaxRate();
        if ($this->productIsInclusiveTax($item) && !empty($productTaxRate)) {
            $taxRates[] = $productTaxRate;
        }

        $price = $item->getPriceInclTax();
        if ($item->getProductType() === "simple") {
            $parentItem = $item->getParentItem();
            if (!empty($parentItem)) {
                $price = $parentItem->getPriceInclTax();
            }
        }

        $data = [
            'customer'    => $customerId,
            'price_data'  => [
                'currency'    => $order->getOrderCurrencyCode(),
                'product'     => $item->getProductId(),
                'unit_amount' => $this->helper->convertMagentoAmountToStripeAmount(
                    $price,
                    $order->getOrderCurrencyCode()
                )
            ],
            'currency'    => $order->getOrderCurrencyCode(),
            'description' => $item->getName(),
            'quantity'    => $item->getQtyOrdered(),
            'tax_rates'   => $taxRates,
            'discounts'   => $this->getDiscountItems($order, $item)
        ];

        $this->createObject($data);

        if (!$this->object) {
            throw new LocalizedException(
                __("The invoice item for product \"%1\" could not be created in Stripe", $item->getName())
            );
        }

        return $this;
    }

    /**
     * From Shipping
     *
     * @param Order $order
     * @param string $customerId
     * @return $this
     * @throws LocalizedException
     */
    public function fromShipping($order, $customerId): InvoiceItem
    {
        $currency = $order->getOrderCurrencyCode();
        $amount = $this->helper->convertMagentoAmountToStripeAmount($order->getShippingInclTax(), $currency);
        if (!$amount || $amount <= 0) {
            return $this;
        }

        $taxRates = [];
        $productTaxRate = $this->getProductTaxRate();
        if (!empty($productTaxRate)) {
            $taxRates[] = $productTaxRate;
        }

        $data = [
            'customer'    => $customerId,
            'amount'      => $amount,
            'currency'    => $currency,
            'description' => __("Shipping"),
            'tax_rates'   => $taxRates,
            'discounts'   => $this->getDiscountShipping(
                $order->getShippingDiscountAmount(),
                $currency
            )
        ];

        $this->createObject($data);

        if (!$this->object) {
            throw new LocalizedException(
                __("The shipping amount for order #%1 could not be created in Stripe", $order->getIncrementId())
            );
        }

        return $this;
    }

    /**
     * Product Is Inclusive Tax
     *
     * @param Item $item
     * @return bool
     */
    private function productIsInclusiveTax(Item $item): bool
    {
        $parentItem = $item->getParentItem();
        if (!empty($parentItem)) {
            $taxPercent = $parentItem->getTaxPercent();
        } else {
            $taxPercent = $item->getTaxPercent();
        }

        if (empty($taxPercent)) {
            return false;
        }

        return true;
    }

    /**
     * Get Product Tax Rate
     *
     * @return mixed
     */
    private function getProductTaxRate()
    {
        if ($this->taxRateId !== null) {
            return $this->taxRateId;
        }
        return $this->taxRateId = $this->config->getTaxRateId();
    }

    /**
     * Get Discount Items
     *
     * @param Order $order
     * @param Item $item
     * @return array
     */
    public function getDiscountItems(Order $order, Item $item): array
    {
        $coupons = [];

        $parentItem = $item->getParentItem();
        if (!empty($parentItem)) {
            $discountAmount = $parentItem->getBaseDiscountAmount();
        } else {
            $discountAmount = $item->getBaseDiscountAmount();
        }

        if (empty($discountAmount)) {
            return $coupons;
        }

        $currency = $order->getOrderCurrencyCode();
        $coupon = $this->couponFactory->create()->getBasicCoupon($discountAmount, null, $currency);
        $couponObject = $coupon->getStripeObject();
        if (!empty($couponObject)) {
            $coupons[] = ['coupon' => $couponObject->id];
        }

        return $coupons;
    }

    /**
     * Get Discount Shipping
     *
     * @param string|float|null $amount
     * @param string $currency
     * @return array
     */
    private function getDiscountShipping($amount, string $currency): array
    {
        $coupons = [];

        if (empty($amount)) {
            return $coupons;
        }

        $name = __("Shipping Discount")->render();
        $coupon = $this->couponFactory->create()->getBasicCoupon($amount, $name, $currency);
        $couponObject = $coupon->getStripeObject();
        if (!empty($couponObject)) {
            $coupons[] = ['coupon' => $couponObject->id];
        }

        return $coupons;
    }
}
