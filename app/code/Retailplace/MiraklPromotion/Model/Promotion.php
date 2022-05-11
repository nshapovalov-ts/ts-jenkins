<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model;

use Exception;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Mirakl\Core\Domain\MiraklObject;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Model\ResourceModel\Promotion as PromotionResourceModel;
use Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescriptionFactory;
use Mirakl\MMP\Common\Domain\Promotion\PromotionMediaFactory;

/**
 * Class Promotion
 */
class Promotion extends AbstractModel implements PromotionInterface
{
    /** @var string */
    public const TABLE_NAME = 'mirakl_promotion';

    /** @var string */
    public const CACHE_TAG = 'retailplace_mirakl_promotion';

    /** @var string */
    protected $_cacheTag = 'retailplace_mirakl_promotion';

    /** @var string */
    protected $_eventPrefix = 'retailplace_mirakl_promotion';

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    private $jsonSerializer;

    /** @var \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescriptionFactory */
    private $promotionPublicDescriptionFactory;

    /** @var \Mirakl\MMP\Common\Domain\Promotion\PromotionMediaFactory */
    private $promotionMediaFactory;

    /**
     * Promotion Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescriptionFactory $promotionPublicDescriptionFactory
     * @param \Mirakl\MMP\Common\Domain\Promotion\PromotionMediaFactory $promotionMediaFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Json $jsonSerializer,
        PromotionPublicDescriptionFactory $promotionPublicDescriptionFactory,
        PromotionMediaFactory $promotionMediaFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->jsonSerializer = $jsonSerializer;
        $this->promotionPublicDescriptionFactory = $promotionPublicDescriptionFactory;
        $this->promotionMediaFactory = $promotionMediaFactory;
    }

    /**
     * Init model
     */
    protected function _construct()
    {
        $this->_init(PromotionResourceModel::class);
    }

    /**
     * Get Entity Id
     *
     * @return int
     */
    public function getPromotionId(): int
    {
        return (int)$this->getData(self::PROMOTION_ID);
    }

    /**
     * Set Entity Id
     *
     * @param int $promotionId
     * @return PromotionInterface
     */
    public function setPromotionId(int $promotionId): PromotionInterface
    {
        return $this->setData(self::PROMOTION_ID, $promotionId);
    }

    /**
     * Get Shop Id
     *
     * @return int
     */
    public function getShopId(): int
    {
        return (int)$this->getData(self::SHOP_ID);
    }

    /**
     * Set Shop Id
     *
     * @param int $shopId
     * @return PromotionInterface
     */
    public function setShopId(int $shopId): PromotionInterface
    {
        return $this->setData(self::SHOP_ID, $shopId);
    }

    /**
     * Get Internal Id
     *
     * @return string|null
     */
    public function getInternalId(): ?string
    {
        return $this->getData(self::INTERNAL_ID);
    }

    /**
     * Set Internal Id
     *
     * @param string $internalId
     * @return PromotionInterface
     */
    public function setInternalId(string $internalId): PromotionInterface
    {
        return $this->setData(self::INTERNAL_ID, $internalId);
    }

    /**
     * Get Promotion Unique Id
     *
     * @return string|null
     */
    public function getPromotionUniqueId(): ?string
    {
        return $this->getData(self::PROMOTION_UNIQUE_ID);
    }

    /**
     * Set Promotion Unique Id
     *
     * @param string $promotionUniqueId
     * @return PromotionInterface
     */
    public function setPromotionUniqueId(string $promotionUniqueId): PromotionInterface
    {
        return $this->setData(self::PROMOTION_UNIQUE_ID, $promotionUniqueId);
    }

    /**
     * Get State
     *
     * @return int
     */
    public function getState(): int
    {
        return (int)$this->getData(self::STATE);
    }

    /**
     * Set State
     *
     * @param int $state
     * @return PromotionInterface
     */
    public function setState(int $state): PromotionInterface
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * Get Type
     *
     * @return int
     */
    public function getType(): int
    {
        return (int)$this->getData(self::TYPE);
    }

    /**
     * Set Type
     *
     * @param int $type
     * @return PromotionInterface
     */
    public function setType(int $type): PromotionInterface
    {
        return $this->setData(self::TYPE, $type);
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
     * @return PromotionInterface
     */
    public function setDateCreated(string $dateCreated): PromotionInterface
    {
        return $this->setData(self::DATE_CREATED, $dateCreated);
    }

    /**
     * Get Date Updated
     *
     * @return string|null
     */
    public function getDateUpdated(): ?string
    {
        return $this->getData(self::DATE_UPDATED);
    }

    /**
     * Set Date Updated
     *
     * @param string $dateUpdated
     * @return PromotionInterface
     */
    public function setDateUpdated(string $dateUpdated): PromotionInterface
    {
        return $this->setData(self::DATE_UPDATED, $dateUpdated);
    }

    /**
     * Get Start Date
     *
     * @return string|null
     */
    public function getStartDate(): ?string
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * Set Start Date
     *
     * @param string $startDate
     * @return PromotionInterface
     */
    public function setStartDate(string $startDate): PromotionInterface
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * Get End Date
     *
     * @return string|null
     */
    public function getEndDate(): ?string
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * Set End Date
     *
     * @param string $endDate
     * @return PromotionInterface
     */
    public function setEndDate(string $endDate): PromotionInterface
    {
        return $this->setData(self::END_DATE, $endDate);
    }

    /**
     * Get Internal Description
     *
     * @return string|null
     */
    public function getInternalDescription(): ?string
    {
        return $this->getData(self::INTERNAL_DESCRIPTION);
    }

    /**
     * Set Internal Description
     *
     * @param string $internalDescription
     * @return PromotionInterface
     */
    public function setInternalDescription(string $internalDescription): PromotionInterface
    {
        return $this->setData(self::INTERNAL_DESCRIPTION, $internalDescription);
    }

    /**
     * Get Percentage Off
     *
     * @return float
     */
    public function getPercentageOff(): float
    {
        return (float)$this->getData(self::PERCENTAGE_OFF);
    }

    /**
     * Set Percentage Off
     *
     * @param float $percentageOff
     * @return PromotionInterface
     */
    public function setPercentageOff(float $percentageOff): PromotionInterface
    {
        return $this->setData(self::PERCENTAGE_OFF, $percentageOff);
    }

    /**
     * Get Amount Off
     *
     * @return float
     */
    public function getAmountOff(): float
    {
        return (float)$this->getData(self::AMOUNT_OFF);
    }

    /**
     * Set Amount Off
     *
     * @param float $amountOff
     * @return PromotionInterface
     */
    public function setAmountOff(float $amountOff): PromotionInterface
    {
        return $this->setData(self::AMOUNT_OFF, $amountOff);
    }

    /**
     * Get Free Items Quantity
     *
     * @return int
     */
    public function getFreeItemsQuantity(): int
    {
        return (int)$this->getData(self::FREE_ITEMS_QUANTITY);
    }

    /**
     * Set Free Items Quantity
     *
     * @param int $freeItemsQuantity
     * @return PromotionInterface
     */
    public function setFreeItemsQuantity(int $freeItemsQuantity): PromotionInterface
    {
        return $this->setData(self::FREE_ITEMS_QUANTITY, $freeItemsQuantity);
    }

    /**
     * Get Public Descriptions
     *
     * @return \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescription[]
     */
    public function getPublicDescriptions(): array
    {
        /** @var \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescription $promotionDescription */
        $promotionDescription = $this->promotionPublicDescriptionFactory->create();

        return $this->unserializeMiraklObjectsArray($this->getData(self::PUBLIC_DESCRIPTIONS), $promotionDescription);
    }

    /**
     * Set Public Descriptions
     *
     * @param \Mirakl\MMP\Common\Domain\Promotion\PromotionPublicDescription[] $publicDescriptions
     * @return PromotionInterface
     */
    public function setPublicDescriptions(array $publicDescriptions): PromotionInterface
    {
        return $this->setData(self::PUBLIC_DESCRIPTIONS, $this->serializeMiraklObjectsArray($publicDescriptions));
    }

    /**
     * Get Reward Offer Ids
     *
     * @return array
     */
    public function getRewardOfferIds(): array
    {
        return explode(',', $this->getData(self::REWARD_OFFER_IDS));
    }

    /**
     * Set Reward Offer Ids
     *
     * @param array $rewardOfferIds
     * @return PromotionInterface
     */
    public function setRewardOfferIds(array $rewardOfferIds): PromotionInterface
    {
        return $this->setData(self::REWARD_OFFER_IDS, implode(',', $rewardOfferIds));
    }

    /**
     * Get Reward On Purchased Items
     *
     * @return bool
     */
    public function getRewardOnPurchasedItems(): bool
    {
        return (bool)$this->getData(self::REWARD_ON_PURCHASED_ITEMS);
    }

    /**
     * Set Reward On Purchased Items
     *
     * @param bool $rewardOnPurchasedItems
     * @return PromotionInterface
     */
    public function setRewardOnPurchasedItems(bool $rewardOnPurchasedItems): PromotionInterface
    {
        return $this->setData(self::REWARD_ON_PURCHASED_ITEMS, $rewardOnPurchasedItems);
    }

    /**
     * Get Trigger Offer Ids
     *
     * @return array
     */
    public function getTriggerOfferIds(): array
    {
        return explode(',', $this->getData(self::TRIGGER_OFFER_IDS));
    }

    /**
     * Set Trigger Offer Ids
     *
     * @param array $triggerOfferIds
     * @return PromotionInterface
     */
    public function setTriggerOfferIds(array $triggerOfferIds): PromotionInterface
    {
        return $this->setData(self::TRIGGER_OFFER_IDS, implode(',', $triggerOfferIds));
    }

    /**
     * Get Media
     *
     * @return \Mirakl\MMP\Common\Domain\Promotion\PromotionMedia[]
     */
    public function getMedia(): array
    {
        /** @var \Mirakl\MMP\Common\Domain\Promotion\PromotionMedia $medias */
        $medias = $this->promotionMediaFactory->create();

        return $this->unserializeMiraklObjectsArray($this->getData(self::MEDIA), $medias);
    }

    /**
     * Set Media
     *
     * @param \Mirakl\MMP\Common\Domain\Promotion\PromotionMedia[] $media
     * @return PromotionInterface
     */
    public function setMedia(array $media): PromotionInterface
    {
        return $this->setData(self::MEDIA, $this->serializeMiraklObjectsArray($media));
    }

    /**
     * Get Localized Public Description (take the first one)
     *
     * @return string
     */
    public function getLocalizedPublicDescription(): string
    {
        $result = '';
        foreach ($this->getPublicDescriptions() as $publicDescription) {
            $result = $publicDescription->getValue();
            break;
        }

        return $result;
    }

    /**
     * Convert Mirakl Objects array to String
     *
     * @param \Mirakl\Core\Domain\MiraklObject[] $objectsArray
     * @return string
     */
    private function serializeMiraklObjectsArray(array $objectsArray): string
    {
        $resultArray = [];
        foreach ($objectsArray as $object) {
            $resultArray[] = $object->toArray();
        }

        try {
            $resultString = $this->jsonSerializer->serialize($resultArray);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
            $resultString = '';
        }

        return $resultString;
    }

    /**
     * Restore Mirakl Objects array from String
     *
     * @param string $objectString
     * @param \Mirakl\Core\Domain\MiraklObject $object
     * @return \Mirakl\Core\Domain\MiraklObject[]
     */
    private function unserializeMiraklObjectsArray(string $objectString, MiraklObject $object)
    {
        $resultArray = [];
        try {
            $objectsArray = $this->jsonSerializer->unserialize($objectString);
            foreach ($objectsArray as $objectArray) {
                $resultArray[] = $object->setData($objectArray);
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $resultArray;
    }
}
