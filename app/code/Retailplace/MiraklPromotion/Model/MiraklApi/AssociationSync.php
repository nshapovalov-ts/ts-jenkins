<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model\MiraklApi;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use DateTimeFactory;
use DateTime;
use Magento\Framework\Stdlib\DateTime as MagentoDateTime;
use Mirakl\Api\Helper\ClientHelper\MMP;
use Mirakl\Api\Model\Client\ClientManager;
use Mirakl\Api\Model\Log\LoggerManager;
use Mirakl\Api\Model\Log\RequestLogValidator;
use Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionOffersMappingCollection;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Mirakl\MMP\FrontOperator\Request\Promotion\PromotionOffersMappingRequestFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Retailplace\MiraklPromotion\Model\PromotionLink;
use Retailplace\MiraklPromotion\Model\SellerSpecialsAttributeUpdater;

/**
 * Class AssociationSync
 */
class AssociationSync extends MMP
{
    /** @var int */
    public const CHUNK_SIZE_TO_LOAD_PROMOTIONS = 1000;

    /** @var string */
    public const XML_PATH_LAST_PROMOTION_MAPPING_SYNC_DATE = 'retailplace_mirakl_promotion/promotions_link_sync/last_sync_date';

    /** @var \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface */
    private $promotionRepository;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Mirakl\MMP\FrontOperator\Request\Promotion\PromotionOffersMappingRequestFactory */
    private $promotionOffersMappingRequestFactory;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /** @var array */
    private $promotionIdMapping = [];

    /** @var \Retailplace\MiraklConnector\Api\OfferRepositoryInterface */
    private $offerRepository;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $config;

    /** @var \DateTimeFactory */
    private $dateTimeFactory;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface */
    private $configWriter;

    /** @var \Retailplace\MiraklPromotion\Model\SellerSpecialsAttributeUpdater */
    private $sellerSpecialsAttributeUpdater;

    /**
     * AssociationSync Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mirakl\Api\Model\Client\ClientManager $clientManager
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Mirakl\Api\Model\Log\LoggerManager $loggerManager
     * @param \Mirakl\Api\Model\Log\RequestLogValidator $requestLogValidator
     * @param \Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface $promotionRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Mirakl\MMP\FrontOperator\Request\Promotion\PromotionOffersMappingRequestFactory $promotionOffersMappingRequestFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Retailplace\MiraklConnector\Api\OfferRepositoryInterface $offerRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \DateTimeFactory $dateTimeFactory
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Retailplace\MiraklPromotion\Model\MiraklApi\SellerSpecialsAttributeUpdater $sellerSpecialsAttributeUpdater
     */
    public function __construct(
        Context $context,
        ClientManager $clientManager,
        CacheInterface $cache,
        LoggerManager $loggerManager,
        RequestLogValidator $requestLogValidator,
        PromotionRepositoryInterface $promotionRepository,
        ResourceConnection $resourceConnection,
        PromotionOffersMappingRequestFactory $promotionOffersMappingRequestFactory,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        OfferRepositoryInterface $offerRepository,
        ScopeConfigInterface $config,
        DateTimeFactory $dateTimeFactory,
        WriterInterface $configWriter,
        SellerSpecialsAttributeUpdater $sellerSpecialsAttributeUpdater
    ) {
        parent::__construct(
            $context,
            $clientManager,
            $cache,
            $loggerManager,
            $requestLogValidator
        );

        $this->resourceConnection = $resourceConnection;
        $this->promotionRepository = $promotionRepository;
        $this->promotionOffersMappingRequestFactory = $promotionOffersMappingRequestFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->offerRepository = $offerRepository;
        $this->config = $config;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->configWriter = $configWriter;
        $this->sellerSpecialsAttributeUpdater = $sellerSpecialsAttributeUpdater;
    }

    /**
     * Get Promotions and Offers Associations from Mirakl
     */
    public function updateAssociations()
    {
        /** @var \Mirakl\MMP\FrontOperator\Request\Promotion\PromotionOffersMappingRequest $request */
        $request = $this->promotionOffersMappingRequestFactory->create();
        $request->setLastRequestDate($this->getLastSyncDate());

        $this->_eventManager->dispatch('mirakl_api_get_promotion_offers_mapping_before', [
            'request' => $request
        ]);

        /** @var \Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionOffersMappingCollection $associationsCollection */
        $associationsCollection = $this->send($request);
        $offerIds = $this->processAssociations($associationsCollection);
        $this->updateLastSyncDate();
        $this->sellerSpecialsAttributeUpdater->update(
            $this->getSkusByOffers($offerIds)
        );
    }

    /**
     * Get Product Skus by Offers
     *
     * @param int[] $offerIds
     * @return string[]
     */
    private function getSkusByOffers(array $offerIds): array
    {
        $skus = [];
        if ($offerIds) {
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter(OfferInterface::OFFER_ENTITY_ID, $offerIds, 'in')
                ->create();
            $offers = $this->offerRepository->getList($searchCriteria);
            foreach ($offers->getItems() as $offer) {
                $skus[] = $offer->getProductSku();
            }
        }

        return array_unique($skus);
    }

    /**
     * Get Last Sync Date from Config
     *
     * @return \DateTime|null
     */
    private function getLastSyncDate(): ?DateTime
    {
        $result = null;
        $date = $this->config->getValue(self::XML_PATH_LAST_PROMOTION_MAPPING_SYNC_DATE);
        if ($date) {
            /** @var \DateTime $datetime */
            $datetime = $this->dateTimeFactory->create();
            $result = $datetime->createFromFormat(MagentoDateTime::DATETIME_PHP_FORMAT, $date);
        }

        return $result;
    }

    /**
     * Update Last Sync Date
     */
    private function updateLastSyncDate()
    {
        /** @var \DateTime $datetime */
        $datetime = $this->dateTimeFactory->create();
        $dateString = $datetime->format(MagentoDateTime::DATETIME_PHP_FORMAT);

        $this->configWriter->save(self::XML_PATH_LAST_PROMOTION_MAPPING_SYNC_DATE, $dateString);
    }

    /**
     * Add Associations to DB
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionOffersMappingCollection $associationsCollection
     * @return array
     */
    private function processAssociations(PromotionOffersMappingCollection $associationsCollection): array
    {
        $insertData = [];
        $this->collectAllRequestPromotions($associationsCollection);
        /** @var \Mirakl\MMP\FrontOperator\Domain\Promotion\PromotionOffersMapping $mapping */
        foreach ($associationsCollection->getItems() as $mapping) {
            foreach ($mapping->getRewardPromotionIds() as $promotionInternalId) {
                $promotionId = $this->getPromotionIdByUniqueId(
                    $this->promotionRepository->generatePromotionUniqueId(
                        $mapping->getShopId(),
                        $promotionInternalId
                    )
                );

                if ($promotionId) {
                    $insertData[] = [
                        'promotion_id' => $promotionId,
                        'offer_id' => $mapping->getOfferId(),
                        'type' => PromotionInterface::LINK_TYPE_REWARD
                    ];
                }
            }

            foreach ($mapping->getTriggerPromotionIds() as $promotionInternalId) {
                $promotionId = $this->getPromotionIdByUniqueId(
                    $this->promotionRepository->generatePromotionUniqueId(
                        $mapping->getShopId(),
                        $promotionInternalId
                    )
                );

                if ($promotionId) {
                    $insertData[] = [
                        'promotion_id' => $promotionId,
                        'offer_id' => $mapping->getOfferId(),
                        'type' => PromotionInterface::LINK_TYPE_TRIGGER
                    ];
                }
            }
        }

        $this->validateEntities($insertData);
        if (count($insertData)) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $this->resourceConnection->getTableName(PromotionLink::TABLE_NAME),
                $insertData
            );
        }

        $offerIds = [];
        foreach ($insertData as $promotionLink) {
            $offerIds[] = $promotionLink['offer_id'];
        }

        return array_unique($offerIds);
    }

    /**
     * Check all Entities Existing in Magento
     *
     * @param array $insertData
     */
    private function validateEntities(array &$insertData)
    {
        $ids = [];
        $existingIds = [];
        foreach ($insertData as $row) {
            $ids[] = $row['offer_id'];
        }

        if (count($ids)) {
            /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter(OfferInterface::OFFER_ENTITY_ID, $ids, 'in')
                ->create();

            $offers = $this->offerRepository->getList($searchCriteria);
            foreach ($offers->getItems() as $offer) {
                $existingIds[] = $offer->getId();
            }
        }

        foreach ($insertData as $key => $row) {
            if (!in_array($row['offer_id'], $existingIds)) {
                unset($insertData[$key]);
            }
        }
    }

    /**
     * Get Promotion Entity Id by Unique Id
     *
     * @param string|null $promotionUniqueId
     * @return int|null
     */
    private function getPromotionIdByUniqueId(?string $promotionUniqueId): ?int
    {
        $promotionId = null;
        if ($promotionUniqueId && !empty($this->promotionIdMapping[$promotionUniqueId])) {
            $promotionId = $this->promotionIdMapping[$promotionUniqueId];
        }

        return $promotionId;
    }

    /**
     * Load all Promotions to use in updating process
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Collection\Promotion\PromotionOffersMappingCollection $associationsCollection
     */
    private function collectAllRequestPromotions(PromotionOffersMappingCollection $associationsCollection)
    {
        $idsList = [];
        /** @var \Mirakl\MMP\FrontOperator\Domain\Promotion\PromotionOffersMapping $mapping */
        foreach ($associationsCollection->getItems() as $mapping) {
            foreach ($mapping->getRewardPromotionIds() as $promotionInternalId) {
                if (!$promotionInternalId) {
                    continue;
                }

                $idsList[] = $this->promotionRepository->generatePromotionUniqueId(
                    $mapping->getShopId(),
                    $promotionInternalId
                );
            }

            foreach ($mapping->getTriggerPromotionIds() as $promotionInternalId) {
                if (!$promotionInternalId) {
                    continue;
                }

                $idsList[] = $this->promotionRepository->generatePromotionUniqueId(
                    $mapping->getShopId(),
                    $promotionInternalId
                );
            }
        }

        $this->loadPromotions($idsList);
    }

    /**
     * Load Promotions from DB to get Entity Ids
     *
     * @param int[] $idsList
     */
    private function loadPromotions(array $idsList)
    {
        $idsList = array_unique($idsList);
        foreach (array_chunk($idsList, self::CHUNK_SIZE_TO_LOAD_PROMOTIONS) as $ids) {
            foreach ($ids as $promotionUniqueId) {
                /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
                $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
                $searchCriteria = $searchCriteriaBuilder
                    ->addFilter(PromotionInterface::PROMOTION_UNIQUE_ID, $promotionUniqueId, 'in')
                    ->create();

                $promotions = $this->promotionRepository->getList($searchCriteria);
                foreach ($promotions->getItems() as $promotion) {
                    $this->promotionIdMapping[$promotion->getPromotionUniqueId()] = $promotion->getPromotionId();
                }
            }
        }
    }
}
