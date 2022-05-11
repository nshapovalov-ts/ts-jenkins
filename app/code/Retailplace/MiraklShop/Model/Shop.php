<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Retailplace\MiraklShop\Api\Data\ShopAmountsInterface;
use Retailplace\MiraklShop\Api\Data\ShopInterface;
use Magento\Framework\Serialize\Serializer\Serialize;
use Magento\Framework\DataObject\Factory as DataObjectFactory;

/**
 * Class Shop
 */
class Shop extends \Mirakl\Core\Model\Shop implements ShopInterface
{
    /** @var \Retailplace\MiraklShop\Model\ShopAmountsManagement */
    private $shopAmountsManagement;

    /** @var \Magento\Framework\Serialize\Serializer\Serialize */
    private $serializer;

    /** @var \Magento\Framework\DataObject\Factory */
    private $dataObjectFactory;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var TimezoneInterface */
    private $timezone;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Retailplace\MiraklShop\Model\ShopAmountsManagement $shopAmountsManagement
     * @param \Magento\Framework\Serialize\Serializer\Serialize $serializer
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        UrlInterface $urlBuilder,
        ShopAmountsManagement $shopAmountsManagement,
        Serialize $serializer,
        DataObjectFactory $dataObjectFactory,
        TimezoneInterface $timezone,
        ScopeConfigInterface $scopeConfig,
        AbstractResource $resource = null,
        AbstractDbCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $urlBuilder,
            $resource,
            $resourceCollection,
            $data
        );

        $this->shopAmountsManagement = $shopAmountsManagement;
        $this->serializer = $serializer;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
    }

    /**
     * Fill out and get Shop Amounts
     *
     * @return \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface
     */
    public function getShopAmounts(): ShopAmountsInterface
    {
        if (!$this->getData(self::SHOP_AMOUNTS)) {
            $this->setShopAmounts($this->shopAmountsManagement->calculateShopAmounts($this));
        }

        return $this->getData(self::SHOP_AMOUNTS);
    }

    /**
     * Shop Amounts setter
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopAmountsInterface $shopAmounts
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     */
    public function setShopAmounts(ShopAmountsInterface $shopAmounts): ShopInterface
    {
        return $this->setData(self::SHOP_AMOUNTS, $shopAmounts);
    }

    /**
     * Get Id
     *
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getData(self::ID);
    }

    /**
     * Set Id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id): ShopInterface
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): ShopInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Eav Option Id
     *
     * @return int
     */
    public function getEavOptionId(): int
    {
        return (int) $this->getData(self::EAV_OPTION_ID);
    }

    /**
     * Set Eav Option Id
     *
     * @param int $eavOptionId
     * @return $this
     */
    public function setEavOptionId(int $eavOptionId): ShopInterface
    {
        return $this->setData(self::EAV_OPTION_ID, $eavOptionId);
    }

    /**
     * Get State
     *
     * @return string|null
     */
    public function getState(): ?string
    {
        return $this->getData(self::STATE);
    }

    /**
     * Set State
     *
     * @param string $state
     * @return $this
     */
    public function setState(string $state): ShopInterface
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * Get Date Created
     *
     * @return string|null
     */
    public function getDateCreated(): ?string
    {
        return $this->getData(self::DATE_CREATED);
    }

    /**
     * Set Date Created
     *
     * @param string $dateCreated
     * @return $this
     */
    public function setDateCreated(string $dateCreated): ShopInterface
    {
        return $this->setData(self::DATE_CREATED, $dateCreated);
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
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): ShopInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get Logo
     *
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->getData(self::LOGO);
    }

    /**
     * Set Logo
     *
     * @param string $logo
     * @return $this
     */
    public function setLogo(string $logo): ShopInterface
    {
        return $this->setData(self::LOGO, $logo);
    }

    /**
     * Get Free Shipping
     *
     * @return bool
     */
    public function getFreeShipping(): bool
    {
        return (bool) $this->getData(self::FREE_SHIPPING);
    }

    /**
     * Set Free Shipping
     *
     * @param bool $freeShipping
     * @return $this
     */
    public function setFreeShipping(bool $freeShipping): ShopInterface
    {
        return $this->setData(self::FREE_SHIPPING, $freeShipping);
    }

    /**
     * Get Professional
     *
     * @return bool
     */
    public function getProfessional(): bool
    {
        return (bool) $this->getData(self::PROFESSIONAL);
    }

    /**
     * Set Professional
     *
     * @param bool $professional
     * @return $this
     */
    public function setProfessional(bool $professional): ShopInterface
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
        return (bool) $this->getData(self::PREMIUM);
    }

    /**
     * Set Premium
     *
     * @param bool $premium
     * @return $this
     */
    public function setPremium(bool $premium): ShopInterface
    {
        return $this->setData(self::PREMIUM, $premium);
    }

    /**
     * Get Closed From
     *
     * @return string|null
     */
    public function getClosedFrom(): ?string
    {
        return $this->getData(self::CLOSED_FROM);
    }

    /**
     * Set Closed From
     *
     * @param string $closedFrom
     * @return $this
     */
    public function setClosedFrom(string $closedFrom): ShopInterface
    {
        return $this->setData(self::CLOSED_FROM, $closedFrom);
    }

    /**
     * Get Closed To
     *
     * @return string|null
     */
    public function getClosedTo(): ?string
    {
        return $this->getData(self::CLOSED_TO);
    }

    /**
     * Set Closed To
     *
     * @param string $closedTo
     * @return $this
     */
    public function setClosedTo(string $closedTo): ShopInterface
    {
        return $this->setData(self::CLOSED_TO, $closedTo);
    }

    /**
     * Get Grade
     *
     * @return float
     */
    public function getGrade(): float
    {
        return (float) $this->getData(self::GRADE);
    }

    /**
     * Set Grade
     *
     * @param float $grade
     * @return $this
     */
    public function setGrade(float $grade): ShopInterface
    {
        return $this->setData(self::GRADE, $grade);
    }

    /**
     * Get Evaluations Count
     *
     * @return int
     */
    public function getEvaluationsCount(): int
    {
        return (int) $this->getData(self::EVALUATIONS_COUNT);
    }

    /**
     * Set Evaluations Count
     *
     * @param int $evaluationsCount
     * @return $this
     */
    public function setEvaluationsCount(int $evaluationsCount): ShopInterface
    {
        return $this->setData(self::EVALUATIONS_COUNT, $evaluationsCount);
    }

    /**
     * Get Additional Info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getAdditionalInfo(): DataObject
    {
        $additionalInfo = $this->getData(self::ADDITIONAL_INFO);
        $dataArray = [];

        if (is_string($additionalInfo)) {
            try {
                $dataArray = $this->serializer->unserialize($additionalInfo);
            } catch (Exception $e) {
                $this->_logger->warning($e->getMessage());
            }
        } elseif (is_array($additionalInfo)) {
            $dataArray = $additionalInfo;
        }

        return $this->dataObjectFactory->create($dataArray);
    }

    /**
     * Set Additional Info
     *
     * @param array $additionalInfo
     * @return $this
     */
    public function setAdditionalInfo(array $additionalInfo): ShopInterface
    {
        try {
            $data = $this->serializer->serialize($additionalInfo);
        } catch (Exception $e) {
            $data = '';
        }

        return $this->setData(self::ADDITIONAL_INFO, $data);
    }

    /**
     * Get Industry Exclusions
     *
     * @return string|null
     */
    public function getIndustryExclusions(): ?string
    {
        return $this->getData(self::INDUSTRY_EXCLUSIONS);
    }

    /**
     * Set Industry Exclusions
     *
     * @param string $industryExclusions
     * @return $this
     */
    public function setIndustryExclusions(string $industryExclusions): ShopInterface
    {
        return $this->setData(self::INDUSTRY_EXCLUSIONS, $industryExclusions);
    }

    /**
     * Get Channel Exclusions
     *
     * @return string|null
     */
    public function getChannelExclusions(): ?string
    {
        return $this->getData(self::CHANNEL_EXCLUSIONS);
    }

    /**
     * Set Channel-exclusions
     *
     * @param string $channelExclusions
     * @return $this
     */
    public function setChannelExclusions(string $channelExclusions): ShopInterface
    {
        return $this->setData(self::CHANNEL_EXCLUSIONS, $channelExclusions);
    }

    /**
     * Get Exclusions Logic
     *
     * @return string|null
     */
    public function getExclusionsLogic(): ?string
    {
        return $this->getData(self::EXCLUSIONS_LOGIC);
    }

    /**
     * Set Exclusions Logic
     *
     * @param string $exclusionsLogic
     * @return $this
     */
    public function setExclusionsLogic(string $exclusionsLogic): ShopInterface
    {
        return $this->setData(self::EXCLUSIONS_LOGIC, $exclusionsLogic);
    }

    /**
     * Get Min Order Amount
     *
     * @return int
     */
    public function getMinOrderAmount(): int
    {
        return (int) $this->getData(self::MIN_ORDER_AMOUNT);
    }

    /**
     * Set Min Order Amount
     *
     * @param int $minOrderAmount
     * @return $this
     */
    public function setMinOrderAmount(int $minOrderAmount): ShopInterface
    {
        return $this->setData(self::MIN_ORDER_AMOUNT, $minOrderAmount);
    }

    /**
     * Get Agha Seller
     *
     * @return bool
     */
    public function getAghaSeller(): bool
    {
        return (bool) $this->getData(self::AGHA_SELLER);
    }

    /**
     * Set Agha Seller
     *
     * @param bool $aghaSeller
     * @return $this
     */
    public function setAghaSeller(bool $aghaSeller): ShopInterface
    {
        return $this->setData(self::AGHA_SELLER, $aghaSeller);
    }

    /**
     * Get Is Fixed Percent Shipping
     *
     * @return bool
     */
    public function getIsFixedPercentShipping(): bool
    {
        return (bool) $this->getData(self::IS_FIXED_PERCENT_SHIPPING);
    }

    /**
     * Set Is Fixed Percent Shipping
     *
     * @param bool $isFixedPercentShipping
     * @return $this
     */
    public function setIsFixedPercentShipping(bool $isFixedPercentShipping): ShopInterface
    {
        return $this->setData(self::IS_FIXED_PERCENT_SHIPPING, $isFixedPercentShipping);
    }

    /**
     * Get Differentiators
     *
     * @return string|null
     */
    public function getDifferentiators(): ?string
    {
        return $this->getData(self::DIFFERENTIATORS);
    }

    /**
     * Set Differentiators
     *
     * @param string $differentiators
     * @return $this
     */
    public function setDifferentiators(string $differentiators): ShopInterface
    {
        return $this->setData(self::DIFFERENTIATORS, $differentiators);
    }

    /**
     * Get Au Post Seller
     *
     * @return bool
     */
    public function getAuPostSeller(): bool
    {
        return (bool) $this->getData(self::AU_POST_SELLER);
    }

    /**
     * Set Au Post Seller
     *
     * @param bool $auPostSeller
     * @return $this
     */
    public function setAuPostSeller(bool $auPostSeller): ShopInterface
    {
        return $this->setData(self::AU_POST_SELLER, $auPostSeller);
    }

    /**
     * Get Open During Xmas
     *
     * @return bool
     */
    public function getOpenDuringXmas(): bool
    {
        return (bool) $this->getData(self::OPEN_DURING_XMAS);
    }

    /**
     * Set Open During Xmas
     *
     * @param bool $openDuringXmas
     * @return $this
     */
    public function setOpenDuringXmas(bool $openDuringXmas): ShopInterface
    {
        return $this->setData(self::OPEN_DURING_XMAS, $openDuringXmas);
    }

    /**
     * Get Holiday Closed From
     *
     * @return string|null
     */
    public function getHolidayClosedFrom(): ?string
    {
        return $this->getData(self::HOLIDAY_CLOSED_FROM);
    }

    /**
     * Set Holiday Closed From
     *
     * @param string $holidayClosedFrom
     * @return $this
     */
    public function setHolidayClosedFrom(string $holidayClosedFrom): ShopInterface
    {
        return $this->setData(self::HOLIDAY_CLOSED_FROM, $holidayClosedFrom);
    }

    /**
     * Get Holiday Closed To
     *
     * @return string|null
     */
    public function getHolidayClosedTo(): ?string
    {
        return $this->getData(self::HOLIDAY_CLOSED_TO);
    }

    /**
     * Set Holiday Closed To
     *
     * @param string $holidayClosedTo
     * @return $this
     */
    public function setHolidayClosedTo(string $holidayClosedTo): ShopInterface
    {
        return $this->setData(self::HOLIDAY_CLOSED_TO, $holidayClosedTo);
    }

    /**
     * Get Min Quote Request Amount
     *
     * @return float
     */
    public function getMinQuoteRequestAmount(): float
    {
        return (float) $this->getData(self::MIN_QUOTE_REQUEST_AMOUNT);
    }

    /**
     * Set Min Quote Request Amount
     *
     * @param float $minQuoteRequestAmount
     * @return $this
     */
    public function setMinQuoteRequestAmount(float $minQuoteRequestAmount): ShopInterface
    {
        return $this->setData(self::MIN_QUOTE_REQUEST_AMOUNT, $minQuoteRequestAmount);
    }

    /**
     * Get Leadtime To Ship
     *
     * @return int
     */
    public function getLeadtimeToShip(): int
    {
        return (int) $this->getData(self::LEADTIME_TO_SHIP);
    }

    /**
     * Set Leadtime To Ship
     *
     * @param int $leadtimeToShip
     * @return $this
     */
    public function setLeadtimeToShip(int $leadtimeToShip): ShopInterface
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
        return (bool) $this->getData(self::ALLOW_QUOTE_REQUESTS);
    }

    /**
     * Set Allow Quote Requests
     *
     * @param bool $allowQuoteRequests
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     */
    public function setAllowQuoteRequests(bool $allowQuoteRequests): ShopInterface
    {
        return $this->setData(self::ALLOW_QUOTE_REQUESTS, $allowQuoteRequests);
    }

    /**
     * Get if the shop is new
     * Check if registration date is less than X days ago (get days count from configs)
     *
     * @return bool
     */
    public function getIsNew(): bool
    {
        $currentDate = $this->timezone->date();
        $daysCount = $this->getNewLabelDaysCount();
        $registrationDate = $this->getData(self::DATE_CREATED);
        $registrationDate = $this->timezone->date($registrationDate);

        return date_diff($registrationDate, $currentDate)->days < $daysCount;
    }

    /**
     * Get days limit to label shops as new
     *
     * @return int
     */
    private function getNewLabelDaysCount(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_NB_DAYS_TO_LABEL_NEW);
    }

    /**
     * Get 'has_new_products' value, if the shop has products marked as "new"
     *
     * @return bool
     */
    public function getHasNewProducts(): bool
    {
        return (bool) $this->getData(self::HAS_NEW_PRODUCTS);
    }

    /**
     * Set 'has_new_products' value
     *
     * @param bool $hasNewProducts
     *
     * @return ShopInterface
     */
    public function setHasNewProducts(bool $hasNewProducts): ShopInterface
    {
        return $this->setData(self::HAS_NEW_PRODUCTS, $hasNewProducts);
    }
}
