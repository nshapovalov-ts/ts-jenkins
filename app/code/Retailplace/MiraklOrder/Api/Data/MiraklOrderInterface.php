<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Api\Data;

/**
 * Interface MiraklOrderInterface
 */
interface MiraklOrderInterface
{
    /**
     * @var string
     */
    const ID = 'entity_id';
    const MIRAKL_ORDER_ID = 'mirakl_order_id';
    const IS_AFFILIATED = 'is_affiliated';
    const MIRAKL_SHOP_ID = 'mirakl_shop_id';
    const MIRAKL_SHOP_NAME = 'mirakl_shop_name';
    const MIRAKL_ORDER_STATUS = 'mirakl_order_status';
    const ORDER_LINES = 'order_lines';
    const HAS_INVOICE = 'has_invoice';
    const HAS_INCIDENT = 'has_incident';
    const TOTAL_COMMISSION = 'total_commission';
    const TOTAL_PRICE = 'total_price';
    const ACTUAL_SHIPPING_AMOUNT = 'actual_shipping_amount';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get order id from mirakl
     *
     * @return string
     */
    public function getMiraklOrderId(): string;

    /**
     * Set mirakl order id
     *
     * @param string $miraklOrderId
     * @return $this
     */
    public function setMiraklOrderId(string $miraklOrderId): MiraklOrderInterface;

    /**
     * Check if order affiliated
     *
     * @return bool
     */
    public function getIsAffiliated(): bool;

    /**
     * Set is order affiliated
     *
     * @param bool $isAffiliated
     * @return $this
     */
    public function setIsAffiliated(bool $isAffiliated): MiraklOrderInterface;

    /**
     * Get Mirakl shop ID
     *
     * @return int
     */
    public function getMiraklShopId(): int;

    /**
     * Set Mirakl shop ID
     *
     * @param int $shopId
     * @return MiraklOrderInterface
     */
    public function setMiraklShopId(int $shopId): MiraklOrderInterface;

    /**
     * Get Mirakl shop name
     *
     * @return string
     */
    public function getMiraklShopName(): string;

    /**
     * Set Mirakl shop name
     *
     * @param string $shopName
     * @return MiraklOrderInterface
     */
    public function setMiraklShopName(string $shopName): MiraklOrderInterface;

    /**
     * Get Mirakl order status
     *
     * @return string
     */
    public function getMiraklOrderStatus(): string;

    /**
     * Set Mirakl order status
     *
     * @param string $orderStatus
     * @return MiraklOrderInterface
     */
    public function setMiraklOrderStatus(string $orderStatus): MiraklOrderInterface;

    /**
     * Get order lines
     *
     * @return int
     */
    public function getOrderLines(): int;

    /**
     * Set order lines
     *
     * @param int $orderLines
     * @return MiraklOrderInterface
     */
    public function setOrderLines(int $orderLines): MiraklOrderInterface;

    /**
     * Get order's has_invoice property
     *
     * @return bool
     */
    public function getHasInvoice(): bool;

    /**
     * Set order's has_invoice property
     *
     * @param bool $hasInvoice
     * @return MiraklOrderInterface
     */
    public function setHasInvoice(bool $hasInvoice): MiraklOrderInterface;

    /**
     * Get order's has_incident property
     *
     * @return bool
     */
    public function getHasIncident(): bool;

    /**
     * Set order's has_incident property
     *
     * @param bool $hasIncident
     * @return MiraklOrderInterface
     */
    public function setHasIncident(bool $hasIncident): MiraklOrderInterface;

    /**
     * Get order's total_commission
     *
     * @return float
     */
    public function getTotalCommission(): float;

    /**
     * Set order's total_commission
     *
     * @param float $totalCommission
     * @return MiraklOrderInterface
     */
    public function setTotalCommission(float $totalCommission): MiraklOrderInterface;

    /**
     * Get order's total_price
     *
     * @return float
     */
    public function getTotalPrice(): float;

    /**
     * Set order's total_price
     *
     * @param float $totalPrice
     * @return MiraklOrderInterface
     */
    public function setTotalPrice(float $totalPrice): MiraklOrderInterface;

    /**
     * Get actual shipping amount
     *
     * @return float
     */
    public function getActualShippingAmount(): float;

    /**
     * Set actual shipping amount
     *
     * @param float $actualShippingAmount
     * @return MiraklOrderInterface
     */
    public function setActualShippingAmount(float $actualShippingAmount): MiraklOrderInterface;

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return MiraklOrderInterface
     */
    public function setCreatedAt(string $createdAt): MiraklOrderInterface;

    /**
     * Get updated_at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated_at
     *
     * @param string $updatedAt
     * @return MiraklOrderInterface
     */
    public function setUpdatedAt(string $updatedAt): MiraklOrderInterface;
}
