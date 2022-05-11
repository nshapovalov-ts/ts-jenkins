<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use phpDocumentor\Reflection\Utils;
use Retailplace\MiraklOrder\Api\Data\MiraklOrderInterface;
use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder as OrderResource;

/**
 * Class MiraklOrder
 */
class MiraklOrder extends AbstractModel implements MiraklOrderInterface, IdentityInterface
{
    /** @var string */
    const CACHE_TAG = 'mirakl_order';

    /** @var string */
    protected $_cacheTag = 'mirakl_order';

    /** @var string */
    protected $_eventPrefix = 'mirakl_order';

    /** @var string */
    protected $_idFieldName = 'entity_id';

    /**
     *  Order constructor.
     */
    protected function _construct()
    {
        $this->_init(OrderResource::class);
    }

    /**
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get order id from mirakl
     *
     * @return string
     */
    public function getMiraklOrderId(): string
    {
        return (string) $this->getData(self::MIRAKL_ORDER_ID);
    }

    /**
     * Set mirakl order id
     *
     * @param string $miraklOrderId
     * @return $this
     */
    public function setMiraklOrderId(string $miraklOrderId): MiraklOrderInterface
    {
        return $this->setData(self::MIRAKL_ORDER_ID, $miraklOrderId);
    }

    /**
     * Check if order affiliated
     *
     * @return bool
     */
    public function getIsAffiliated(): bool
    {
        return (bool) $this->getData(self::IS_AFFILIATED);
    }

    /**
     * Set is order affiliated
     *
     * @param bool $isAffiliated
     * @return $this
     */
    public function setIsAffiliated(bool $isAffiliated): MiraklOrderInterface
    {
        return $this->setData(self::IS_AFFILIATED, $isAffiliated);
    }

    /**
     * Get Mirakl shop ID
     *
     * @return int
     */
    public function getMiraklShopId(): int
    {
        return (int) $this->getData(self::MIRAKL_SHOP_ID);
    }

    /**
     * Set Mirakl shop ID
     *
     * @param int $shopId
     * @return MiraklOrderInterface
     */
    public function setMiraklShopId(int $shopId): MiraklOrderInterface
    {
        return $this->setData(self::MIRAKL_SHOP_ID, $shopId);
    }

    /**
     * Get Mirakl shop name
     *
     * @return string
     */
    public function getMiraklShopName(): string
    {
        return (string) $this->getData(self::MIRAKL_SHOP_NAME);
    }

    /**
     * Set Mirakl shop name
     *
     * @param string $shopName
     * @return MiraklOrderInterface
     */
    public function setMiraklShopName(string $shopName): MiraklOrderInterface
    {
        return $this->setData(self::MIRAKL_SHOP_NAME, $shopName);
    }

    /**
     * Get Mirakl order status
     *
     * @return string
     */
    public function getMiraklOrderStatus(): string
    {
        return (string) $this->getData(self::MIRAKL_ORDER_STATUS);
    }

    /**
     * Set Mirakl order status
     *
     * @param string $orderStatus
     * @return MiraklOrderInterface
     */
    public function setMiraklOrderStatus(string $orderStatus): MiraklOrderInterface
    {
        return $this->setData(self::MIRAKL_ORDER_STATUS, $orderStatus);
    }

    /**
     * Get order lines
     *
     * @return int
     */
    public function getOrderLines(): int
    {
        return (int) $this->getData(self::ORDER_LINES);
    }

    /**
     * Set order lines
     *
     * @param int $orderLines
     * @return MiraklOrderInterface
     */
    public function setOrderLines(int $orderLines): MiraklOrderInterface
    {
        return $this->setData(self::ORDER_LINES, $orderLines);
    }

    /**
     * Get order's has_invoice property
     *
     * @return bool
     */
    public function getHasInvoice(): bool
    {
        return (bool) $this->getData(self::HAS_INVOICE);
    }

    /**
     * Set order's has_invoice property
     *
     * @param bool $hasInvoice
     * @return MiraklOrderInterface
     */
    public function setHasInvoice(bool $hasInvoice): MiraklOrderInterface
    {
        return $this->setData(self::HAS_INVOICE);
    }

    /**
     * Get order's has_incident property
     *
     * @return bool
     */
    public function getHasIncident(): bool
    {
        return (bool) $this->getData(self::HAS_INCIDENT);
    }

    /**
     * Set order's has_incident property
     *
     * @param bool $hasIncident
     * @return MiraklOrderInterface
     */
    public function setHasIncident(bool $hasIncident): MiraklOrderInterface
    {
        return $this->setData(self::HAS_INCIDENT, $hasIncident);
    }

    /**
     * Get order's total_commission
     *
     * @return float
     */
    public function getTotalCommission(): float
    {
        return (float) $this->getData(self::TOTAL_COMMISSION);
    }

    /**
     * Set order's total_commission
     *
     * @param float $totalCommission
     * @return MiraklOrderInterface
     */
    public function setTotalCommission(float $totalCommission): MiraklOrderInterface
    {
        return $this->setData(self::TOTAL_COMMISSION, $totalCommission);
    }

    /**
     * Get order's total_price
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return (float) $this->getData(self::TOTAL_PRICE);
    }

    /**
     * Set order's total_price
     *
     * @param float $totalPrice
     * @return MiraklOrderInterface
     */
    public function setTotalPrice(float $totalPrice): MiraklOrderInterface
    {
        return $this->setData(self::TOTAL_PRICE, $totalPrice);
    }

    /**
     * Get actual shipping amount
     *
     * @return float
     */
    public function getActualShippingAmount(): float
    {
        return (float) $this->getData(self::ACTUAL_SHIPPING_AMOUNT);
    }

    /**
     * Set actual shipping amount
     *
     * @param float $actualShippingAmount
     * @return MiraklOrderInterface
     */
    public function setActualShippingAmount(float $actualShippingAmount): MiraklOrderInterface
    {
        return $this->setData(self::ACTUAL_SHIPPING_AMOUNT, $actualShippingAmount);
    }

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return MiraklOrderInterface
     */
    public function setCreatedAt(string $createdAt): MiraklOrderInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated_at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated_at
     *
     * @param string $updatedAt
     * @return MiraklOrderInterface
     */
    public function setUpdatedAt(string $updatedAt): MiraklOrderInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
