<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Api\Data;

use Magento\Framework\DataObject;

/**
 * Interface ShopInterface
 */
interface ShopInterface
{
    /** @var string */
    public const SHOP_AMOUNTS = 'shop_amounts';
    public const ID = 'id';
    public const NAME = 'name';
    public const EAV_OPTION_ID = 'eav_option_id';
    public const STATE = 'state';
    public const DATE_CREATED = 'date_created';
    public const DESCRIPTION = 'description';
    public const LOGO = 'logo';
    public const FREE_SHIPPING = 'free_shipping';
    public const PROFESSIONAL = 'professional';
    public const PREMIUM = 'premium';
    public const CLOSED_FROM = 'closed_from';
    public const CLOSED_TO = 'closed_to';
    public const GRADE = 'grade';
    public const EVALUATIONS_COUNT = 'evaluations_count';
    public const ADDITIONAL_INFO = 'additional_info';
    public const INDUSTRY_EXCLUSIONS = 'industry-exclusions';
    public const CHANNEL_EXCLUSIONS = 'channel-exclusions';
    public const EXCLUSIONS_LOGIC = 'exclusions-logic';
    public const MIN_ORDER_AMOUNT = 'min-order-amount';
    public const AGHA_SELLER = 'agha_seller';
    public const IS_FIXED_PERCENT_SHIPPING = 'is_fixed_percent_shipping';
    public const DIFFERENTIATORS = 'differentiators';
    public const AU_POST_SELLER = 'au_post_seller';
    public const OPEN_DURING_XMAS = 'open_during_xmas';
    public const HOLIDAY_CLOSED_FROM = 'holiday_closed_from';
    public const HOLIDAY_CLOSED_TO = 'holiday_closed_to';
    public const MIN_QUOTE_REQUEST_AMOUNT = 'min_quote_request_amount';
    public const LEADTIME_TO_SHIP = 'leadtime_to_ship';
    public const ALLOW_QUOTE_REQUESTS = 'allow_quote_requests';
    public const HAS_NEW_PRODUCTS = 'has_new_products';

    /**
     * Path to config for days count to mark products and sellers as new after creation
     */
    public const XML_PATH_NB_DAYS_TO_LABEL_NEW = 'retailplace_attribute_updater/has_new_products/nb_days_to_label_new';

    /**
     * Get Shop Amounts
     *
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function getShopAmounts(): ShopAmountsInterface;

    /**
     * Set Shop Amounts
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface $shopAmounts
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     */
    public function setShopAmounts(ShopAmountsInterface $shopAmounts): ShopInterface;

    /**
     * Get Id
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set Id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id): ShopInterface;

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): ShopInterface;

    /**
     * Get Eav Option Id
     *
     * @return int
     */
    public function getEavOptionId(): int;

    /**
     * Set Eav Option Id
     *
     * @param int $eavOptionId
     * @return $this
     */
    public function setEavOptionId(int $eavOptionId): ShopInterface;

    /**
     * Get State
     *
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * Set State
     *
     * @param string $state
     * @return $this
     */
    public function setState(string $state): ShopInterface;

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
     * @return $this
     */
    public function setDateCreated(string $dateCreated): ShopInterface;

    /**
     * Get Description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set Description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): ShopInterface;

    /**
     * Get Logo
     *
     * @return string|null
     */
    public function getLogo(): ?string;

    /**
     * Set Logo
     *
     * @param string $logo
     * @return $this
     */
    public function setLogo(string $logo): ShopInterface;

    /**
     * Get Free Shipping
     *
     * @return bool
     */
    public function getFreeShipping(): bool;

    /**
     * Set Free Shipping
     *
     * @param bool $freeShipping
     * @return $this
     */
    public function setFreeShipping(bool $freeShipping): ShopInterface;

    /**
     * Get Professional
     *
     * @return bool
     */
    public function getProfessional(): bool;

    /**
     * Set Professional
     *
     * @param bool $professional
     * @return $this
     */
    public function setProfessional(bool $professional): ShopInterface;

    /**
     * Get Premium
     *
     * @return bool
     */
    public function getPremium(): bool;

    /**
     * Set Premium
     *
     * @param bool $premium
     * @return $this
     */
    public function setPremium(bool $premium): ShopInterface;

    /**
     * Get Closed From
     *
     * @return string|null
     */
    public function getClosedFrom(): ?string;

    /**
     * Set Closed From
     *
     * @param string $closedFrom
     * @return $this
     */
    public function setClosedFrom(string $closedFrom): ShopInterface;

    /**
     * Get Closed To
     *
     * @return string|null
     */
    public function getClosedTo(): ?string;

    /**
     * Set Closed To
     *
     * @param string $closedTo
     * @return $this
     */
    public function setClosedTo(string $closedTo): ShopInterface;

    /**
     * Get Grade
     *
     * @return float
     */
    public function getGrade(): float;

    /**
     * Set Grade
     *
     * @param float $grade
     * @return $this
     */
    public function setGrade(float $grade): ShopInterface;

    /**
     * Get Evaluations Count
     *
     * @return int
     */
    public function getEvaluationsCount(): int;

    /**
     * Set Evaluations Count
     *
     * @param int $evaluationsCount
     * @return $this
     */
    public function setEvaluationsCount(int $evaluationsCount): ShopInterface;

    /**
     * Get Additional Info
     *
     * @return DataObject
     */
    public function getAdditionalInfo(): DataObject;

    /**
     * Set Additional Info
     *
     * @param array $additionalInfo
     * @return $this
     */
    public function setAdditionalInfo(array $additionalInfo): ShopInterface;

    /**
     * Get Industry Exclusions
     *
     * @return string|null
     */
    public function getIndustryExclusions(): ?string;

    /**
     * Set Industry Exclusions
     *
     * @param string $industryExclusions
     * @return $this
     */
    public function setIndustryExclusions(string $industryExclusions): ShopInterface;

    /**
     * Get Channel Exclusions
     *
     * @return string|null
     */
    public function getChannelExclusions(): ?string;

    /**
     * Set Channel-exclusions
     *
     * @param string $channelExclusions
     * @return $this
     */
    public function setChannelExclusions(string $channelExclusions): ShopInterface;

    /**
     * Get Exclusions Logic
     *
     * @return string|null
     */
    public function getExclusionsLogic(): ?string;

    /**
     * Set Exclusions Logic
     *
     * @param string $exclusionsLogic
     * @return $this
     */
    public function setExclusionsLogic(string $exclusionsLogic): ShopInterface;

    /**
     * Get Min Order Amount
     *
     * @return int
     */
    public function getMinOrderAmount(): int;

    /**
     * Set Min Order Amount
     *
     * @param int $minOrderAmount
     * @return $this
     */
    public function setMinOrderAmount(int $minOrderAmount): ShopInterface;

    /**
     * Get Agha Seller
     *
     * @return bool
     */
    public function getAghaSeller(): bool;

    /**
     * Set Agha Seller
     *
     * @param bool $aghaSeller
     * @return $this
     */
    public function setAghaSeller(bool $aghaSeller): ShopInterface;

    /**
     * Get Is Fixed Percent Shipping
     *
     * @return bool
     */
    public function getIsFixedPercentShipping(): bool;

    /**
     * Set Is Fixed Percent Shipping
     *
     * @param bool $isFixedPercentShipping
     * @return $this
     */
    public function setIsFixedPercentShipping(bool $isFixedPercentShipping): ShopInterface;

    /**
     * Get Differentiators
     *
     * @return string|null
     */
    public function getDifferentiators(): ?string;

    /**
     * Set Differentiators
     *
     * @param string $differentiators
     * @return $this
     */
    public function setDifferentiators(string $differentiators): ShopInterface;

    /**
     * Get Au Post Seller
     *
     * @return bool
     */
    public function getAuPostSeller(): bool;

    /**
     * Set Au Post Seller
     *
     * @param bool $auPostSeller
     * @return $this
     */
    public function setAuPostSeller(bool $auPostSeller): ShopInterface;

    /**
     * Get Open During Xmas
     *
     * @return bool
     */
    public function getOpenDuringXmas(): bool;

    /**
     * Set Open During Xmas
     *
     * @param bool $openDuringXmas
     * @return $this
     */
    public function setOpenDuringXmas(bool $openDuringXmas): ShopInterface;

    /**
     * Get Holiday Closed From
     *
     * @return string|null
     */
    public function getHolidayClosedFrom(): ?string;

    /**
     * Set Holiday Closed From
     *
     * @param string $holidayClosedFrom
     * @return $this
     */
    public function setHolidayClosedFrom(string $holidayClosedFrom): ShopInterface;

    /**
     * Get Holiday Closed To
     *
     * @return string|null
     */
    public function getHolidayClosedTo(): ?string;

    /**
     * Set Holiday Closed To
     *
     * @param string $holidayClosedTo
     * @return $this
     */
    public function setHolidayClosedTo(string $holidayClosedTo): ShopInterface;

    /**
     * Get Min Quote Request Amount
     *
     * @return float
     */
    public function getMinQuoteRequestAmount(): float;

    /**
     * Set Min Quote Request Amount
     *
     * @param float $minQuoteRequestAmount
     * @return $this
     */
    public function setMinQuoteRequestAmount(float $minQuoteRequestAmount): ShopInterface;

    /**
     * Get Leadtime To Ship
     *
     * @return int
     */
    public function getLeadtimeToShip(): int;

    /**
     * Set Leadtime To Ship
     *
     * @param int $leadtimeToShip
     * @return $this
     */
    public function setLeadtimeToShip(int $leadtimeToShip): ShopInterface;

    /**
     * Get Allow Quote Requests
     *
     * @return bool
     */
    public function getAllowQuoteRequests(): bool;

    /**
     * Set Allow Quote Requests
     *
     * @param bool $allowQuoteRequests
     * @return $this
     */
    public function setAllowQuoteRequests(bool $allowQuoteRequests): ShopInterface;

    /**
     * Get if the shop is new
     *
     * @return bool
     */
    public function getIsNew(): bool;

    /**
     * Get 'has_new_products' value, if the shop has products marked as "new"
     *
     * @return bool
     */
    public function getHasNewProducts(): bool;

    /**
     * Set 'has_new_products' value
     *
     * @param bool $hasNewProducts
     *
     * @return ShopInterface
     */
    public function setHasNewProducts(bool $hasNewProducts): ShopInterface;
}
