<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime;
use Mirakl\MMP\FrontOperator\Domain\Promotion\Promotion as MiraklPromotion;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterfaceFactory;
use Retailplace\MiraklPromotion\Api\PromotionRepositoryInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterfaceFactory;
use Retailplace\MiraklPromotion\Model\ResourceModel\Promotion as PromotionResourceModel;
use Retailplace\MiraklPromotion\Model\ResourceModel\Promotion\CollectionFactory as PromotionCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

/**
 * Class PromotionRepository
 */
class PromotionRepository implements PromotionRepositoryInterface
{
    /** @var string */
    public const PROMOTION_UNIQUE_ID_SEPARATOR = '-';

    /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[] */
    private $promotionsList;

    /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterfaceFactory */
    private $promotionFactory;

    /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\Promotion */
    private $promotionResourceModel;

    /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterfaceFactory */
    private $promotionSearchResultFactory;

    /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\Promotion\CollectionFactory */
    private $promotionCollectionFactory;

    /** @var \Magento\Framework\Api\SearchCriteria\CollectionProcessor */
    private $collectionProcessor;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory */
    private $dateTimeFactory;

    /** @var \Magento\Framework\Api\FilterBuilder */
    private $filterBuilder;

    /** @var \Magento\Framework\Api\Search\FilterGroupBuilder */
    private $filterGroupBuilder;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory */
    private $searchCriteriaBuilderFactory;

    /**
     * PromotionRepository constructor.
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterfaceFactory $promotionFactory
     * @param \Retailplace\MiraklPromotion\Model\ResourceModel\Promotion $promotionResourceModel
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterfaceFactory $promotionSearchResultFactory
     * @param \Retailplace\MiraklPromotion\Model\ResourceModel\Promotion\CollectionFactory $promotionCollectionFactory
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessor $collectionProcessor
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        PromotionInterfaceFactory $promotionFactory,
        PromotionResourceModel $promotionResourceModel,
        PromotionSearchResultsInterfaceFactory $promotionSearchResultFactory,
        PromotionCollectionFactory $promotionCollectionFactory,
        CollectionProcessor $collectionProcessor,
        DateTimeFactory $dateTimeFactory,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->promotionFactory = $promotionFactory;
        $this->promotionResourceModel = $promotionResourceModel;
        $this->promotionSearchResultFactory = $promotionSearchResultFactory;
        $this->promotionCollectionFactory = $promotionCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Get Promotion by Id
     *
     * @param int $promotionId
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $promotionId): PromotionInterface
    {
        if (!isset($this->promotionsList[$promotionId])) {
            /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterface|\Retailplace\MiraklPromotion\Model\Promotion $promotion */
            $promotion = $this->promotionFactory->create();
            $this->promotionResourceModel->load($promotion, $promotionId);
            if (!$promotion->getId()) {
                throw new NoSuchEntityException(__('Unable to find Mirakl Promotion with ID "%1"', $promotionId));
            }

            $this->promotionsList[$promotionId] = $promotion;
        }

        return $this->promotionsList[$promotionId];
    }

    /**
     * Save Promotion
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterface $promotion
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(PromotionInterface $promotion): PromotionInterface
    {
        $this->promotionResourceModel->save($promotion);

        return $promotion;
    }

    /**
     * Delete Promotion
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterface $promotion
     * @return bool
     * @throws \Exception
     */
    public function delete(PromotionInterface $promotion): bool
    {
        unset($this->promotionsList[$promotion->getId()]);
        $this->promotionResourceModel->delete($promotion);

        return true;
    }

    /**
     * Delete Promotion by Id
     *
     * @param int $promotionId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function deleteById(int $promotionId): bool
    {
        $promotion = $this->getById($promotionId);

        return $this->delete($promotion);
    }

    /**
     * Convert Mirakl Promotion to Retailplace Promotion
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Promotion\Promotion $miraklPromotion
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface
     */
    public function convertMiraklPromotion(MiraklPromotion $miraklPromotion): PromotionInterface
    {
        /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterface $promotion */
        $promotion = $this->promotionFactory->create();
        $promotion->setData($miraklPromotion->getData());

        $promotion->setState(PromotionInterface::STATES[$miraklPromotion->getState()] ?? 0);
        $promotion->setType(PromotionInterface::TYPES[$miraklPromotion->getType()] ?? 0);

        $promotion->setRewardOfferIds($miraklPromotion->getRewardOfferIds());
        $promotion->setTriggerOfferIds($miraklPromotion->getTriggerOfferIds());
        $promotion->setPromotionUniqueId($this->generatePromotionUniqueId(
            (int) $miraklPromotion->getShopId(),
            $miraklPromotion->getInternalId()
        ));

        if ($miraklPromotion->getPublicDescriptions()) {
            $promotion->setPublicDescriptions($miraklPromotion->getPublicDescriptions()->getItems());
        }
        if ($miraklPromotion->getMedia()) {
            $promotion->setMedia($miraklPromotion->getMedia()->getItems());
        }
        if ($miraklPromotion->getDateCreated()) {
            $promotion->setDateCreated($miraklPromotion->getDateCreated()->format(DateTime::DATETIME_PHP_FORMAT));
        }
        if ($miraklPromotion->getStartDate()) {
            $promotion->setStartDate($miraklPromotion->getStartDate()->format(DateTime::DATETIME_PHP_FORMAT));
        }
        if ($miraklPromotion->getEndDate()) {
            $promotion->setEndDate($miraklPromotion->getEndDate()->format(DateTime::DATETIME_PHP_FORMAT));
        }

        return $promotion;
    }

    /**
     * Generate Unique ID for Promotion
     *
     * @param int $shopId
     * @param string $promotionInternalId
     * @return string
     */
    public function generatePromotionUniqueId(int $shopId, string $promotionInternalId): string
    {
        return $shopId . self::PROMOTION_UNIQUE_ID_SEPARATOR . $promotionInternalId;
    }

    /**
     * Get Promotions List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResults
    {
        /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface $searchResult */
        $searchResult = $this->promotionSearchResultFactory->create();

        /** @var \Retailplace\MiraklPromotion\Model\ResourceModel\Promotion\Collection $collection */
        $collection = $this->promotionCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[] $items */
        $items = $collection->getItems();

        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * Get Active Promotions by Shops List
     *
     * @param array $shopIds
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface
     */
    public function getActiveByShops(array $shopIds): SearchResults
    {
        $now = $this->dateTimeFactory->create();
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $filterEndDate1 = $this->filterBuilder
            ->setField(PromotionInterface::END_DATE)
            ->setConditionType("gteq")
            ->setValue($now->gmtDate())
            ->create();
        $filterEndDate2 = $this->filterBuilder
            ->setField(PromotionInterface::END_DATE)
            ->setConditionType("null")
            ->setValue(true)
            ->create();
        $filterGroup = $this->filterGroupBuilder
            ->addFilter($filterEndDate1)
            ->addFilter($filterEndDate2)
            ->create();
        $searchCriteria = $searchCriteriaBuilder->setFilterGroups([$filterGroup])
            ->addFilter(PromotionInterface::SHOP_ID, $shopIds, 'in')
            ->addFilter(PromotionInterface::STATE, PromotionInterface::STATE_ACTIVE)
            ->addFilter(PromotionInterface::START_DATE, $now->gmtDate(), 'lteq')
            ->create();

        return $this->getList($searchCriteria);
    }
}
