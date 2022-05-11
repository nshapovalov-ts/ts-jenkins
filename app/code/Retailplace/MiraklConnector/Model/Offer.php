<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Model;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Model\Context;
use Magento\Framework\Pricing\PriceInfo\Factory as PriceInfoFactory;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Mirakl\Connector\Model\ResourceModel;
use Mirakl\Connector\Model\ResourceModel\Offer\Collection as OfferCollection;
use Mirakl\Core\Model\ResourceModel\Offer\State\CollectionFactory as StateCollectionFactory;
use Mirakl\Core\Model\ResourceModel\ShopFactory as ShopResourceFactory;
use Mirakl\Core\Model\ShopFactory;
use Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Mirakl\Connector\Model\Offer as MiraklOffer;

/**
 * Class Offer
 */
class Offer extends MiraklOffer implements SaleableInterface, OfferInterface
{
    /** @var \Magento\Framework\Serialize\SerializerInterface */
    private $serializer;

    /**
     * Offer constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Mirakl\Connector\Model\ResourceModel\Offer $resource
     * @param \Mirakl\Connector\Model\ResourceModel\Offer\Collection $resourceCollection
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param \Magento\Framework\Pricing\PriceInfo\Factory $priceInfoFactory
     * @param \Mirakl\Core\Model\ResourceModel\Offer\State\CollectionFactory $stateCollectionFactory
     * @param \Mirakl\Core\Model\ShopFactory $shopFactory
     * @param \Mirakl\Core\Model\ResourceModel\ShopFactory $shopResourceFactory
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ResourceModel\Offer $resource,
        OfferCollection $resourceCollection,
        ProductType $catalogProductType,
        PriceInfoFactory $priceInfoFactory,
        StateCollectionFactory $stateCollectionFactory,
        ShopFactory $shopFactory,
        ShopResourceFactory $shopResourceFactory,
        SerializerInterface $serializer,
        $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $catalogProductType,
            $priceInfoFactory,
            $stateCollectionFactory,
            $shopFactory,
            $shopResourceFactory,
            $data
        );

        $this->serializer = $serializer;
    }

    /**
     * Get Offer Id
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getData(self::OFFER_ENTITY_ID);
    }

    /**
     * Set Offer Id
     *
     * @return $this
     * @var int $offerId
     */
    public function setId($offerId): OfferInterface
    {
        return $this->setData(self::OFFER_ENTITY_ID, $offerId);
    }

    /**
     * Get Offer Id
     *
     * @return int
     */
    public function getOfferId(): int
    {
        return (int) $this->getData(self::OFFER_ENTITY_ID);
    }

    /**
     * Set Offer Id
     *
     * @return $this
     * @var int $offerId
     */
    public function setOfferId(int $offerId): OfferInterface
    {
        return $this->setData(self::OFFER_ENTITY_ID, $offerId);
    }

    /**
     * Get Product Sku
     *
     * @return string|null
     */
    public function getProductSku(): ?string
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * Set Product Sku
     *
     * @return $this
     * @var string $productSku
     */
    public function setProductSku(string $productSku): OfferInterface
    {
        return $this->setData(self::PRODUCT_SKU, $productSku);
    }

    /**
     * Get Min Shipping Price
     *
     * @return float
     */
    public function getMinShippingPrice(): float
    {
        return (float) $this->getData(self::MIN_SHIPPING_PRICE);
    }

    /**
     * Set Min Shipping Price
     *
     * @return $this
     * @var float $minShippingPrice
     */
    public function setMinShippingPrice(float $minShippingPrice): OfferInterface
    {
        return $this->setData(self::MIN_SHIPPING_PRICE, $minShippingPrice);
    }

    /**
     * Get Min Shipping Price Additional
     *
     * @return float
     */
    public function getMinShippingPriceAdditional(): float
    {
        return (float) $this->getData(self::MIN_SHIPPING_PRICE_ADDITIONAL);
    }

    /**
     * Set Min Shipping Price Additional
     *
     * @return $this
     * @var float $minShippingPriceAdditional
     */
    public function setMinShippingPriceAdditional(float $minShippingPriceAdditional): OfferInterface
    {
        return $this->setData(self::MIN_SHIPPING_PRICE_ADDITIONAL, $minShippingPriceAdditional);
    }

    /**
     * Get Min Shipping Zone
     *
     * @return string|null
     */
    public function getMinShippingZone(): ?string
    {
        return $this->getData(self::MIN_SHIPPING_ZONE);
    }

    /**
     * Set Min Shipping Zone
     *
     * @return $this
     * @var string $minShippingZone
     */
    public function setMinShippingZone(string $minShippingZone): OfferInterface
    {
        return $this->setData(self::MIN_SHIPPING_ZONE, $minShippingZone);
    }

    /**
     * Get Min Shipping Type
     *
     * @return string|null
     */
    public function getMinShippingType(): ?string
    {
        return $this->getData(self::MIN_SHIPPING_TYPE);
    }

    /**
     * Set Min Shipping Type
     *
     * @return $this
     * @var string $minShippingType
     */
    public function setMinShippingType(string $minShippingType): OfferInterface
    {
        return $this->setData(self::MIN_SHIPPING_TYPE, $minShippingType);
    }

    /**
     * Get Price
     *
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->getData(self::PRICE);
    }

    /**
     * Set Price
     *
     * @return $this
     * @var float $price
     */
    public function setPrice(float $price): OfferInterface
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * Get Total Price
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return (float) $this->getData(self::TOTAL_PRICE);
    }

    /**
     * Set Total Price
     *
     * @return $this
     * @var float $totalPrice
     */
    public function setTotalPrice(float $totalPrice): OfferInterface
    {
        return $this->setData(self::TOTAL_PRICE, $totalPrice);
    }

    /**
     * Get Price Additional Info
     *
     * @return string|null
     */
    public function getPriceAdditionalInfo(): ?string
    {
        return $this->getData(self::PRICE_ADDITIONAL_INFO);
    }

    /**
     * Set Price Additional Info
     *
     * @return $this
     * @var string $priceAdditionalInfo
     */
    public function setPriceAdditionalInfo(string $priceAdditionalInfo): OfferInterface
    {
        return $this->setData(self::PRICE_ADDITIONAL_INFO, $priceAdditionalInfo);
    }

    /**
     * Get Quantity
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return (int) $this->getData(self::QUANTITY);
    }

    /**
     * Set Quantity
     *
     * @return $this
     * @var int $quantity
     */
    public function setQuantity(int $quantity): OfferInterface
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set Description
     *
     * @return $this
     * @var string $description
     */
    public function setDescription(string $description): OfferInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get State Code
     *
     * @return int
     */
    public function getStateCode(): int
    {
        return (int) $this->getData(self::STATE_CODE);
    }

    /**
     * Set State Code
     *
     * @return $this
     * @var int $stateCode
     */
    public function setStateCode(int $stateCode): OfferInterface
    {
        return $this->setData(self::STATE_CODE, $stateCode);
    }

    /**
     * Get Shop Id
     *
     * @return int
     */
    public function getShopId(): int
    {
        return (int) $this->getData(self::SHOP_ID);
    }

    /**
     * Set Shop Id
     *
     * @return $this
     * @var int $shopId
     */
    public function setShopId(int $shopId): OfferInterface
    {
        return $this->setData(self::SHOP_ID, $shopId);
    }

    /**
     * Get Shop Name
     *
     * @return string|null
     */
    public function getShopName(): ?string
    {
        return $this->getData(self::SHOP_NAME);
    }

    /**
     * Set Shop Name
     *
     * @return $this
     * @var string $shopName
     */
    public function setShopName(string $shopName): OfferInterface
    {
        return $this->setData(self::SHOP_NAME, $shopName);
    }

    /**
     * Get Professional
     *
     * @return bool
     */
    public function getProfessional(): bool
    {
        return $this->getData(self::PROFESSIONAL) === 'true';
    }

    /**
     * Set Professional
     *
     * @return $this
     * @var string $professional
     */
    public function setProfessional(string $professional): OfferInterface
    {
        return $this->setData(self::PROFESSIONAL, $professional);
    }

    /**
     * Get Premium
     *
     * @return bool
     */
    public function getPremium(): bool
    {
        return $this->getData(self::PREMIUM) === 'true';
    }

    /**
     * Set Premium
     *
     * @return $this
     * @var string $premium
     */
    public function setPremium(string $premium): OfferInterface
    {
        return $this->setData(self::PREMIUM, $premium);
    }

    /**
     * Get Logistic Class
     *
     * @return string|null
     */
    public function getLogisticClass(): ?string
    {
        return $this->getData(self::LOGISTIC_CLASS);
    }

    /**
     * Set Logistic Class
     *
     * @return $this
     * @var string $logisticClass
     */
    public function setLogisticClass(string $logisticClass): OfferInterface
    {
        return $this->setData(self::LOGISTIC_CLASS, $logisticClass);
    }

    /**
     * Get Active
     *
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->getData(self::ACTIVE) === 'true';
    }

    /**
     * Set Active
     *
     * @return $this
     * @var string $active
     */
    public function setActive(string $active): OfferInterface
    {
        return $this->setData(self::ACTIVE, $active);
    }

    /**
     * Get Favorite Rank
     *
     * @return int
     */
    public function getFavoriteRank(): int
    {
        return (int) $this->getData(self::FAVORITE_RANK);
    }

    /**
     * Set Favorite Rank
     *
     * @return $this
     * @var int $favoriteRank
     */
    public function setFavoriteRank(int $favoriteRank): OfferInterface
    {
        return $this->setData(self::FAVORITE_RANK, $favoriteRank);
    }

    /**
     * Get Channels
     *
     * @return string|null
     */
    public function getChannels(): ?string
    {
        return $this->getData(self::CHANNELS);
    }

    /**
     * Set Channels
     *
     * @return $this
     * @var string $channels
     */
    public function setChannels(string $channels): OfferInterface
    {
        return $this->setData(self::CHANNELS, $channels);
    }

    /**
     * Get Deleted
     *
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->getData(self::DELETED) === 'true';
    }

    /**
     * Set Deleted
     *
     * @return $this
     * @var string $deleted
     */
    public function setDeleted(string $deleted): OfferInterface
    {
        return $this->setData(self::DELETED, $deleted);
    }

    /**
     * Get Origin Price
     *
     * @return float
     */
    public function getOriginPrice(): float
    {
        return (float) $this->getData(self::ORIGIN_PRICE);
    }

    /**
     * Set Origin Price
     *
     * @return $this
     * @var float $originPrice
     */
    public function setOriginPrice(float $originPrice): OfferInterface
    {
        return $this->setData(self::ORIGIN_PRICE, $originPrice);
    }

    /**
     * Get Discount Start Date
     *
     * @return string|null
     */
    public function getDiscountStartDate(): ?string
    {
        return $this->getData(self::DISCOUNT_START_DATE);
    }

    /**
     * Set Discount Start Date
     *
     * @return $this
     * @var string $discountStartDate
     */
    public function setDiscountStartDate(string $discountStartDate): OfferInterface
    {
        return $this->setData(self::DISCOUNT_START_DATE, $discountStartDate);
    }

    /**
     * Get Discount End Date
     *
     * @return string|null
     */
    public function getDiscountEndDate(): ?string
    {
        return $this->getData(self::DISCOUNT_END_DATE);
    }

    /**
     * Set Discount End Date
     *
     * @return $this
     * @var string $discountEndDate
     */
    public function setDiscountEndDate(string $discountEndDate): OfferInterface
    {
        return $this->setData(self::DISCOUNT_END_DATE, $discountEndDate);
    }

    /**
     * Get Available Start Date
     *
     * @return string|null
     */
    public function getAvailableStartDate(): ?string
    {
        return $this->getData(self::AVAILABLE_START_DATE);
    }

    /**
     * Set Available Start Date
     *
     * @return $this
     * @var string $availableStartDate
     */
    public function setAvailableStartDate(string $availableStartDate): OfferInterface
    {
        return $this->setData(self::AVAILABLE_START_DATE, $availableStartDate);
    }

    /**
     * Get Available End Date
     *
     * @return string|null
     */
    public function getAvailableEndDate(): ?string
    {
        return $this->getData(self::AVAILABLE_END_DATE);
    }

    /**
     * Set Available End Date
     *
     * @return $this
     * @var string $availableEndDate
     */
    public function setAvailableEndDate(string $availableEndDate): OfferInterface
    {
        return $this->setData(self::AVAILABLE_END_DATE, $availableEndDate);
    }

    /**
     * Get Discount Price
     *
     * @return float
     */
    public function getDiscountPrice(): float
    {
        return (float) $this->getData(self::DISCOUNT_PRICE);
    }

    /**
     * Set Discount Price
     *
     * @return $this
     * @var float $discountPrice
     */
    public function setDiscountPrice(float $discountPrice): OfferInterface
    {
        return $this->setData(self::DISCOUNT_PRICE, $discountPrice);
    }

    /**
     * Get Currency Iso Code
     *
     * @return string|null
     */
    public function getCurrencyIsoCode(): ?string
    {
        return $this->getData(self::CURRENCY_ISO_CODE);
    }

    /**
     * Set Currency Iso Code
     *
     * @return $this
     * @var string $currencyIsoCode
     */
    public function setCurrencyIsoCode(string $currencyIsoCode): OfferInterface
    {
        return $this->setData(self::CURRENCY_ISO_CODE, $currencyIsoCode);
    }

    /**
     * Get Discount Ranges
     *
     * @return string|null
     */
    public function getDiscountRanges(): ?string
    {
        return $this->getData(self::DISCOUNT_RANGES);
    }

    /**
     * Set Discount Ranges
     *
     * @return $this
     * @var string $discountRanges
     */
    public function setDiscountRanges(string $discountRanges): OfferInterface
    {
        return $this->setData(self::DISCOUNT_RANGES, $discountRanges);
    }

    /**
     * Get Leadtime To Ship
     *
     * @return int|null
     */
    public function getLeadtimeToShip(): ?int
    {
        return parent::getLeadtimeToShip();
    }

    /**
     * Set Leadtime To Ship
     *
     * @return $this
     * @var string $leadtimeToShip
     */
    public function setLeadtimeToShip(string $leadtimeToShip): OfferInterface
    {
        return $this->setData(self::LEADTIME_TO_SHIP, $leadtimeToShip);
    }

    /**
     * Get Allow Quote Requests
     *
     * @return bool
     */
    public function getAllowQuoteRequests(): bool
    {
        return $this->getData(self::ALLOW_QUOTE_REQUESTS) === 'true';
    }

    /**
     * Set Allow Quote Requests
     *
     * @return $this
     * @var string $allowQuoteRequests
     */
    public function setAllowQuoteRequests(string $allowQuoteRequests): OfferInterface
    {
        return $this->setData(self::ALLOW_QUOTE_REQUESTS, $allowQuoteRequests);
    }

    /**
     * Get Price Ranges
     *
     * @return \Mirakl\MMP\Common\Domain\Collection\DiscountRangeCollection
     */
    public function getPriceRanges(): DiscountRangeCollection
    {
        return parent::getPriceRanges();
    }

    /**
     * Set Price Ranges
     *
     * @return $this
     * @var string $priceRanges
     */
    public function setPriceRanges(string $priceRanges): OfferInterface
    {
        return $this->setData(self::PRICE_RANGES, $priceRanges);
    }

    /**
     * Get Min Order Quantity
     *
     * @return int
     */
    public function getMinOrderQuantity(): int
    {
        return (int) $this->getData(self::MIN_ORDER_QUANTITY);
    }

    /**
     * Set Min Order Quantity
     *
     * @return $this
     * @var int $minOrderQuantity
     */
    public function setMinOrderQuantity(int $minOrderQuantity): OfferInterface
    {
        return $this->setData(self::MIN_ORDER_QUANTITY, $minOrderQuantity);
    }

    /**
     * Get Max Order Quantity
     *
     * @return int
     */
    public function getMaxOrderQuantity(): int
    {
        return (int) $this->getData(self::MAX_ORDER_QUANTITY);
    }

    /**
     * Set Max Order Quantity
     *
     * @return $this
     * @var int $maxOrderQuantity
     */
    public function setMaxOrderQuantity(int $maxOrderQuantity): OfferInterface
    {
        return $this->setData(self::MAX_ORDER_QUANTITY, $maxOrderQuantity);
    }

    /**
     * Get Package Quantity
     *
     * @return int
     */
    public function getPackageQuantity(): int
    {
        return (int) $this->getData(self::PACKAGE_QUANTITY);
    }

    /**
     * Set Package Quantity
     *
     * @return $this
     * @var int $packageQuantity
     */
    public function setPackageQuantity(int $packageQuantity): OfferInterface
    {
        return $this->setData(self::PACKAGE_QUANTITY, $packageQuantity);
    }

    /**
     * Get Product Tax Code
     *
     * @return string|null
     */
    public function getProductTaxCode(): ?string
    {
        return $this->getData(self::PRODUCT_TAX_CODE);
    }

    /**
     * Set Product Tax Code
     *
     * @return $this
     * @var string $productTaxCode
     */
    public function setProductTaxCode(string $productTaxCode): OfferInterface
    {
        return $this->setData(self::PRODUCT_TAX_CODE, $productTaxCode);
    }

    /**
     * Get Clearance
     *
     * @return int
     */
    public function getClearance(): int
    {
        return (int) $this->getData(self::CLEARANCE);
    }

    /**
     * Set Clearance
     *
     * @return $this
     * @var int $clearance
     */
    public function setClearance(int $clearance): OfferInterface
    {
        return $this->setData(self::CLEARANCE, $clearance);
    }

    /**
     * Get Additional Info
     *
     * @return array
     */
    public function getAdditionalInfo(): array
    {
        try {
            $additionalInfo = $this->serializer->unserialize($this->getData(self::ADDITIONAL_INFO));
        } catch (\Exception $e) {
            $additionalInfo = [];
            $this->_logger->error($e->getMessage());
        }

        return $additionalInfo;
    }

    /**
     * Set Additional Info
     *
     * @return $this
     * @var string $additionalInfo
     */
    public function setAdditionalInfo(string $additionalInfo): OfferInterface
    {
        return $this->setData(self::ADDITIONAL_INFO, $additionalInfo);
    }

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set Created At
     *
     * @return $this
     * @var string $createdAt
     */
    public function setCreatedAt(string $createdAt): OfferInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set Updated At
     *
     * @return $this
     * @var string $updatedAt
     */
    public function setUpdatedAt(string $updatedAt): OfferInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get Segment
     *
     * @return string|null
     */
    public function getSegment(): ?string
    {
        return $this->getData(self::SEGMENT);
    }

    /**
     * Set Segment
     *
     * @return $this
     * @var string $segment
     */
    public function setSegment(string $segment): OfferInterface
    {
        return $this->setData(self::SEGMENT, $segment);
    }

    /**
     * Get Shop Sku
     *
     * @return string|null
     */
    public function getShopSku(): ?string
    {
        return $this->getData(self::SHOP_SKU);
    }

    /**
     * Set Shop Sku
     *
     * @return $this
     * @var string $shopSku
     */
    public function setShopSku(string $shopSku): OfferInterface
    {
        return $this->setData(self::SHOP_SKU, $shopSku);
    }
}
