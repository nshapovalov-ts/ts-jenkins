<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Api\Data;

/**
 * Interface PromotionInterface
 */
interface PromotionInterface
{
    /**
     * Entity Keys
     *
     * @var string
     */
    public const ENTITY_ID = 'promotion_id';
    public const PROMOTION_ID = 'promotion_id';
    public const SHOP_ID = 'shop_id';
    public const INTERNAL_ID = 'internal_id';
    public const PROMOTION_UNIQUE_ID = 'promotion_unique_id';
    public const STATE = 'state';
    public const TYPE = 'type';
    public const DATE_CREATED = 'date_created';
    public const DATE_UPDATED = 'date_updated';
    public const START_DATE = 'start_date';
    public const END_DATE = 'end_date';
    public const INTERNAL_DESCRIPTION = 'internal_description';
    public const PERCENTAGE_OFF = 'percentage_off';
    public const AMOUNT_OFF = 'amount_off';
    public const FREE_ITEMS_QUANTITY = 'free_items_quantity';
    public const PUBLIC_DESCRIPTIONS = 'public_descriptions';
    public const REWARD_OFFER_IDS = 'reward_offer_ids';
    public const REWARD_ON_PURCHASED_ITEMS = 'reward_on_purchased_items';
    public const TRIGGER_OFFER_IDS = 'trigger_offer_ids';
    public const MEDIA = 'media';

    /**
     * Promotion States
     *
     * @var int
     */
    public const STATE_ACTIVE = 1;
    public const STATE_PENDING = 2;
    public const STATE_PENDING_APPROVAL = 3;
    public const STATE_REJECTED = 4;
    public const STATE_EXPIRED = 5;

    /**
     * Promotion States Mapping
     *
     * @var string[]
     */
    public const STATES = [
        'ACTIVE' => self::STATE_ACTIVE,
        'PENDING' => self::STATE_PENDING,
        'PENDING_APPROVAL' => self::STATE_PENDING_APPROVAL,
        'REJECTED' => self::STATE_REJECTED,
        'EXPIRED' => self::STATE_EXPIRED
    ];

    /**
     * Promotion Discount Types
     *
     * @var int
     */
    public const TYPE_AMOUNT_OFF = 1;
    public const TYPE_FREE_ITEMS = 2;
    public const TYPE_PERCENTAGE_OFF = 3;

    /**
     * Promotion Discount Types Mapping
     *
     * @var string[]
     */
    public const TYPES = [
        'AMOUNT_OFF' => self::TYPE_AMOUNT_OFF,
        'FREE_ITEMS' => self::TYPE_FREE_ITEMS,
        'PERCENTAGE_OFF' => self::TYPE_PERCENTAGE_OFF,
    ];

    /**
     * Links Type between Offer and Promotion
     *
     * @var int
     */
    public const LINK_TYPE_REWARD = 1;
    public const LINK_TYPE_TRIGGER = 2;

    /**
     * Get Promotion Id
     *
     * @return int
     */
    public function getPromotionId(): int;

    /**
     * Set Promotion Id
     *
     * @param int $promotionId
     * @return PromotionInterface
     */
    public function setPromotionId(int $promotionId): PromotionInterface;

    /**
     * Get Shop Id
     *
     * @return int
     */
    public function getShopId(): int;

    /**
     * Set Shop Id
     *
     * @param int $shopId
     * @return PromotionInterface
     */
    public function setShopId(int $shopId): PromotionInterface;

    /**
     * Get Internal Id
     *
     * @return string|null
     */
    public function getInternalId(): ?string;

    /**
     * Set Internal Id
     *
     * @param string $internalId
     * @return PromotionInterface
     */
    public function setInternalId(string $internalId): PromotionInterface;

    /**
     * Get Promotion Unique Id
     *
     * @return string|null
     */
    public function getPromotionUniqueId(): ?string;

    /**
     * Set Promotion Unique Id
     *
     * @param string $promotionUniqueId
     * @return PromotionInterface
     */
    public function setPromotionUniqueId(string $promotionUniqueId): PromotionInterface;

    /**
     * Get State
     *
     * @return int
     */
    public function getState(): int;

    /**
     * Set State
     *
     * @param int $state
     * @return PromotionInterface
     */
    public function setState(int $state): PromotionInterface;

    /**
     * Get Type
     *
     * @return int
     */
    public function getType(): int;

    /**
     * Set Type
     *
     * @param int $type
     * @return PromotionInterface
     */
    public function setType(int $type): PromotionInterface;

    /**
     * Get Date Created
     *
     * @return string|null
     */
    public function getDateCreated(): ?string;

    /**
     * Set Date Created
     *
     * @param string $dateCreated
     * @return PromotionInterface
     */
    public function setDateCreated(string $dateCreated): PromotionInterface;

    /**
     * Get Date Updated
     *
     * @return string|null
     */
    public function getDateUpdated(): ?string;

    /**
     * Set Date Updated
     *
     * @param string $dateUpdated
     * @return PromotionInterface
     */
    public function setDateUpdated(string $dateUpdated): PromotionInterface;

    /**
     * Get Start Date
     *
     * @return string|null
     */
    public function getStartDate(): ?string;

    /**
     * Set Start Date
     *
     * @param string $startDate
     * @return PromotionInterface
     */
    public function setStartDate(string $startDate): PromotionInterface;

    /**
     * Get End Date
     *
     * @return string|null
     */
    public function getEndDate(): ?string;

    /**
     * Set End Date
     *
     * @param string $endDate
     * @return PromotionInterface
     */
    public function setEndDate(string $endDate): PromotionInterface;

    /**
     * Get Internal Description
     *
     * @return string|null
     */
    public function getInternalDescription(): ?string;

    /**
     * Set Internal Description
     *
     * @param string $internalDescription
     * @return PromotionInterface
     */
    public function setInternalDescription(string $internalDescription): PromotionInterface;

    /**
     * Get Percentage Off
     *
     * @return float
     */
    public function getPercentageOff(): float;

    /**
     * Set Percentage Off
     *
     * @param float $percentageOff
     * @return PromotionInterface
     */
    public function setPercentageOff(float $percentageOff): PromotionInterface;

    /**
     * Get Amount Off
     *
     * @return float
     */
    public function getAmountOff(): float;

    /**
     * Set Amount Off
     *
     * @param float $amountOff
     * @return PromotionInterface
     */
    public function setAmountOff(float $amountOff): PromotionInterface;

    /**
     * Get Free Items Quantity
     *
     * @return int
     */
    public function getFreeItemsQuantity(): int;

    /**
     * Set Free Items Quantity
     *
     * @param int $freeItemsQuantity
     * @return PromotionInterface
     */
    public function setFreeItemsQuantity(int $freeItemsQuantity): PromotionInterface;

    /**
     * Get Public Descriptions
     *
     * @return \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescription[]
     */
    public function getPublicDescriptions(): array;

    /**
     * Set Public Descriptions
     *
     * @param \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescription[] $publicDescriptions
     * @return PromotionInterface
     */
    public function setPublicDescriptions(array $publicDescriptions): PromotionInterface;

    /**
     * Get Reward Offer Ids
     *
     * @return array
     */
    public function getRewardOfferIds(): array;

    /**
     * Set Reward Offer Ids
     *
     * @param array $rewardOfferIds
     * @return PromotionInterface
     */
    public function setRewardOfferIds(array $rewardOfferIds): PromotionInterface;

    /**
     * Get Reward On Purchased Items
     *
     * @return bool
     */
    public function getRewardOnPurchasedItems(): bool;

    /**
     * Set Reward On Purchased Items
     *
     * @param bool $rewardOnPurchasedItems
     * @return PromotionInterface
     */
    public function setRewardOnPurchasedItems(bool $rewardOnPurchasedItems): PromotionInterface;

    /**
     * Get Trigger Offer Ids
     *
     * @return array
     */
    public function getTriggerOfferIds(): array;

    /**
     * Set Trigger Offer Ids
     *
     * @param array $triggerOfferIds
     * @return PromotionInterface
     */
    public function setTriggerOfferIds(array $triggerOfferIds): PromotionInterface;

    /**
     * Get Media
     *
     * @return \Mirakl\MMP\Common\Domain\Promotion\PromotionMedia[]
     */
    public function getMedia(): array;

    /**
     * Set Media
     *
     * @param \Mirakl\MMP\Common\Domain\Promotion\PromotionMedia[] $media
     * @return PromotionInterface
     */
    public function setMedia(array $media): PromotionInterface;

    /**
     * Get Localized Public Description (take the first one)
     *
     * @return string
     */
    public function getLocalizedPublicDescription(): string;
}
