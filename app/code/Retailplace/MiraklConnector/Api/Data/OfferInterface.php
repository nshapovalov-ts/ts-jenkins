<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Api\Data;

use Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection;

/**
 * Interface OfferInterface
 */
interface OfferInterface
{
    /** @var string */
    public const OFFER_ENTITY_ID = 'offer_id';
    public const PRODUCT_SKU = 'product_sku';
    public const MIN_SHIPPING_PRICE = 'min_shipping_price';
    public const MIN_SHIPPING_PRICE_ADDITIONAL = 'min_shipping_price_additional';
    public const MIN_SHIPPING_ZONE = 'min_shipping_zone';
    public const MIN_SHIPPING_TYPE = 'min_shipping_type';
    public const PRICE = 'price';
    public const TOTAL_PRICE = 'total_price';
    public const PRICE_ADDITIONAL_INFO = 'price_additional_info';
    public const QUANTITY = 'quantity';
    public const DESCRIPTION = 'description';
    public const STATE_CODE = 'state_code';
    public const SHOP_ID = 'shop_id';
    public const SHOP_NAME = 'shop_name';
    public const PROFESSIONAL = 'professional';
    public const PREMIUM = 'premium';
    public const LOGISTIC_CLASS = 'logistic_class';
    public const ACTIVE = 'active';
    public const FAVORITE_RANK = 'favorite_rank';
    public const CHANNELS = 'channels';
    public const DELETED = 'deleted';
    public const ORIGIN_PRICE = 'origin_price';
    public const DISCOUNT_START_DATE = 'discount_start_date';
    public const DISCOUNT_END_DATE = 'discount_end_date';
    public const AVAILABLE_START_DATE = 'available_start_date';
    public const AVAILABLE_END_DATE = 'available_end_date';
    public const DISCOUNT_PRICE = 'discount_price';
    public const CURRENCY_ISO_CODE = 'currency_iso_code';
    public const DISCOUNT_RANGES = 'discount_ranges';
    public const LEADTIME_TO_SHIP = 'leadtime_to_ship';
    public const ALLOW_QUOTE_REQUESTS = 'allow_quote_requests';
    public const PRICE_RANGES = 'price_ranges';
    public const MIN_ORDER_QUANTITY = 'min_order_quantity';
    public const MAX_ORDER_QUANTITY = 'max_order_quantity';
    public const PACKAGE_QUANTITY = 'package_quantity';
    public const PRODUCT_TAX_CODE = 'product_tax_code';
    public const CLEARANCE = 'clearance';
    public const ADDITIONAL_INFO = 'additional_info';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const SEGMENT = 'segment';
    public const SHOP_SKU = 'shop_sku';

    /**
     * Get Id
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set Id
     *
     * @param int $offerId
     * @return $this
     */
    public function setId($offerId): OfferInterface;

    /**
     * Get Offer Id
     *
     * @return int
     */
    public function getOfferId(): int;

    /**
     * Set Offer Id
     *
     * @var int $offerId
     * @return $this
     */
    public function setOfferId(int $offerId): OfferInterface;

    /**
     * Get Product Sku
     *
     * @return string|null
     */
    public function getProductSku(): ?string;

    /**
     * Set Product Sku
     *
     * @var string $productSku
     * @return $this
     */
    public function setProductSku(string $productSku): OfferInterface;

    /**
     * Get Min Shipping Price
     *
     * @return float
     */
    public function getMinShippingPrice(): float;

    /**
     * Set Min Shipping Price
     *
     * @var float $minShippingPrice
     * @return $this
     */
    public function setMinShippingPrice(float $minShippingPrice): OfferInterface;

    /**
     * Get Min Shipping Price Additional
     *
     * @return float
     */
    public function getMinShippingPriceAdditional(): float;

    /**
     * Set Min Shipping Price Additional
     *
     * @var float $minShippingPriceAdditional
     * @return $this
     */
    public function setMinShippingPriceAdditional(float $minShippingPriceAdditional): OfferInterface;

    /**
     * Get Min Shipping Zone
     *
     * @return string|null
     */
    public function getMinShippingZone(): ?string;

    /**
     * Set Min Shipping Zone
     *
     * @var string $minShippingZone
     * @return $this
     */
    public function setMinShippingZone(string $minShippingZone): OfferInterface;

    /**
     * Get Min Shipping Type
     *
     * @return string|null
     */
    public function getMinShippingType(): ?string;

    /**
     * Set Min Shipping Type
     *
     * @var string $minShippingType
     * @return $this
     */
    public function setMinShippingType(string $minShippingType): OfferInterface;

    /**
     * Get Price
     *
     * @return float
     */
    public function getPrice(): float;

    /**
     * Set Price
     *
     * @var float $price
     * @return $this
     */
    public function setPrice(float $price): OfferInterface;

    /**
     * Get Total Price
     *
     * @return float
     */
    public function getTotalPrice(): float;

    /**
     * Set Total Price
     *
     * @var float $totalPrice
     * @return $this
     */
    public function setTotalPrice(float $totalPrice): OfferInterface;

    /**
     * Get Price Additional Info
     *
     * @return string|null
     */
    public function getPriceAdditionalInfo(): ?string;

    /**
     * Set Price Additional Info
     *
     * @var string $priceAdditionalInfo
     * @return $this
     */
    public function setPriceAdditionalInfo(string $priceAdditionalInfo): OfferInterface;

    /**
     * Get Quantity
     *
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Set Quantity
     *
     * @var int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): OfferInterface;

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set Description
     *
     * @var string $description
     * @return $this
     */
    public function setDescription(string $description): OfferInterface;

    /**
     * Get State Code
     *
     * @return int
     */
    public function getStateCode(): int;

    /**
     * Set State Code
     *
     * @var int $stateCode
     * @return $this
     */
    public function setStateCode(int $stateCode): OfferInterface;

    /**
     * Get Shop Id
     *
     * @return int
     */
    public function getShopId(): int;

    /**
     * Set Shop Id
     *
     * @var int $shopId
     * @return $this
     */
    public function setShopId(int $shopId): OfferInterface;

    /**
     * Get Shop Name
     *
     * @return string|null
     */
    public function getShopName(): ?string;

    /**
     * Set Shop Name
     *
     * @var string $shopName
     * @return $this
     */
    public function setShopName(string $shopName): OfferInterface;

    /**
     * Get Professional
     *
     * @return bool
     */
    public function getProfessional(): bool;

    /**
     * Set Professional
     *
     * @var string $professional
     * @return $this
     */
    public function setProfessional(string $professional): OfferInterface;

    /**
     * Get Premium
     *
     * @return bool
     */
    public function getPremium(): bool;

    /**
     * Set Premium
     *
     * @var string $premium
     * @return $this
     */
    public function setPremium(string $premium): OfferInterface;

    /**
     * Get Logistic Class
     *
     * @return string|null
     */
    public function getLogisticClass(): ?string;

    /**
     * Set Logistic Class
     *
     * @var string $logisticClass
     * @return $this
     */
    public function setLogisticClass(string $logisticClass): OfferInterface;

    /**
     * Get Active
     *
     * @return bool
     */
    public function getActive(): bool;

    /**
     * Set Active
     *
     * @var string $active
     * @return $this
     */
    public function setActive(string $active): OfferInterface;

    /**
     * Get Favorite Rank
     *
     * @return int
     */
    public function getFavoriteRank(): int;

    /**
     * Set Favorite Rank
     *
     * @var int $favoriteRank
     * @return $this
     */
    public function setFavoriteRank(int $favoriteRank): OfferInterface;

    /**
     * Get Channels
     *
     * @return string|null
     */
    public function getChannels(): ?string;

    /**
     * Set Channels
     *
     * @var string $channels
     * @return $this
     */
    public function setChannels(string $channels): OfferInterface;

    /**
     * Get Deleted
     *
     * @return bool
     */
    public function getDeleted(): bool;

    /**
     * Set Deleted
     *
     * @var string $deleted
     * @return $this
     */
    public function setDeleted(string $deleted): OfferInterface;

    /**
     * Get Origin Price
     *
     * @return float
     */
    public function getOriginPrice(): float;

    /**
     * Set Origin Price
     *
     * @var float $originPrice
     * @return $this
     */
    public function setOriginPrice(float $originPrice): OfferInterface;

    /**
     * Get Discount Start Date
     *
     * @return string|null
     */
    public function getDiscountStartDate(): ?string;

    /**
     * Set Discount Start Date
     *
     * @var string $discountStartDate
     * @return $this
     */
    public function setDiscountStartDate(string $discountStartDate): OfferInterface;

    /**
     * Get Discount End Date
     *
     * @return string|null
     */
    public function getDiscountEndDate(): ?string;

    /**
     * Set Discount End Date
     *
     * @var string $discountEndDate
     * @return $this
     */
    public function setDiscountEndDate(string $discountEndDate): OfferInterface;

    /**
     * Get Available Start Date
     *
     * @return string|null
     */
    public function getAvailableStartDate(): ?string;

    /**
     * Set Available Start Date
     *
     * @var string $availableStartDate
     * @return $this
     */
    public function setAvailableStartDate(string $availableStartDate): OfferInterface;

    /**
     * Get Available End Date
     *
     * @return string|null
     */
    public function getAvailableEndDate(): ?string;

    /**
     * Set Available End Date
     *
     * @var string $availableEndDate
     * @return $this
     */
    public function setAvailableEndDate(string $availableEndDate): OfferInterface;

    /**
     * Get Discount Price
     *
     * @return float
     */
    public function getDiscountPrice(): float;

    /**
     * Set Discount Price
     *
     * @var float $discountPrice
     * @return $this
     */
    public function setDiscountPrice(float $discountPrice): OfferInterface;

    /**
     * Get Currency Iso Code
     *
     * @return string|null
     */
    public function getCurrencyIsoCode(): ?string;

    /**
     * Set Currency Iso Code
     *
     * @var string $currencyIsoCode
     * @return $this
     */
    public function setCurrencyIsoCode(string $currencyIsoCode): OfferInterface;

    /**
     * Get Discount Ranges
     *
     * @return string|null
     */
    public function getDiscountRanges(): ?string;

    /**
     * Set Discount Ranges
     *
     * @var string $discountRanges
     * @return $this
     */
    public function setDiscountRanges(string $discountRanges): OfferInterface;

    /**
     * Get Leadtime To Ship
     *
     * @return int|null
     */
    public function getLeadtimeToShip(): ?int;

    /**
     * Set Leadtime To Ship
     *
     * @var string $leadtimeToShip
     * @return $this
     */
    public function setLeadtimeToShip(string $leadtimeToShip): OfferInterface;

    /**
     * Get Allow Quote Requests
     *
     * @return bool
     */
    public function getAllowQuoteRequests(): bool;

    /**
     * Set Allow Quote Requests
     *
     * @var string $allowQuoteRequests
     * @return $this
     */
    public function setAllowQuoteRequests(string $allowQuoteRequests): OfferInterface;

    /**
     * Get Price Ranges
     *
     * @return \Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection
     */
    public function getPriceRanges(): DiscountRangeCollection;

    /**
     * Set Price Ranges
     *
     * @var string $priceRanges
     * @return $this
     */
    public function setPriceRanges(string $priceRanges): OfferInterface;

    /**
     * Get Min Order Quantity
     *
     * @return int
     */
    public function getMinOrderQuantity(): int;

    /**
     * Set Min Order Quantity
     *
     * @var int $minOrderQuantity
     * @return $this
     */
    public function setMinOrderQuantity(int $minOrderQuantity): OfferInterface;

    /**
     * Get Max Order Quantity
     *
     * @return int
     */
    public function getMaxOrderQuantity(): int;

    /**
     * Set Max Order Quantity
     *
     * @var int $maxOrderQuantity
     * @return $this
     */
    public function setMaxOrderQuantity(int $maxOrderQuantity): OfferInterface;

    /**
     * Get Package Quantity
     *
     * @return int
     */
    public function getPackageQuantity(): int;

    /**
     * Set Package Quantity
     *
     * @var int $packageQuantity
     * @return $this
     */
    public function setPackageQuantity(int $packageQuantity): OfferInterface;

    /**
     * Get Product Tax Code
     *
     * @return string|null
     */
    public function getProductTaxCode(): ?string;

    /**
     * Set Product Tax Code
     *
     * @var string $productTaxCode
     * @return $this
     */
    public function setProductTaxCode(string $productTaxCode): OfferInterface;

    /**
     * Get Clearance
     *
     * @return int
     */
    public function getClearance(): int;

    /**
     * Set Clearance
     *
     * @var int $clearance
     * @return $this
     */
    public function setClearance(int $clearance): OfferInterface;

    /**
     * Get Additional Info
     *
     * @return array
     */
    public function getAdditionalInfo(): array;

    /**
     * Set Additional Info
     *
     * @var string $additionalInfo
     * @return $this
     */
    public function setAdditionalInfo(string $additionalInfo): OfferInterface;

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Created At
     *
     * @var string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): OfferInterface;

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set Updated At
     *
     * @var string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): OfferInterface;

    /**
     * Get Segment
     *
     * @return string|null
     */
    public function getSegment(): ?string;

    /**
     * Set Segment
     *
     * @var string $segment
     * @return $this
     */
    public function setSegment(string $segment): OfferInterface;

    /**
     * Get Shop Sku
     *
     * @return string|null
     */
    public function getShopSku(): ?string;

    /**
     * Set Shop Sku
     *
     * @var string $shopSku
     * @return $this
     */
    public function setShopSku(string $shopSku): OfferInterface;
}
