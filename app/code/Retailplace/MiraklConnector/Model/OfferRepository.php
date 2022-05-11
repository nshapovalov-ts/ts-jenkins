<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\NoSuchEntityException;
use Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory as OfferCollectionFactory;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;
use Retailplace\MiraklConnector\Api\Data\OfferInterfaceFactory;
use Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterfaceFactory;
use Retailplace\MiraklConnector\Api\OfferRepositoryInterface;
use Mirakl\Connector\Model\ResourceModel\Offer as OfferResourceModel;

/**
 * Class OfferRepository
 */
class OfferRepository implements OfferRepositoryInterface
{
    /** @var \Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterfaceFactory */
    private $offerSearchResultFactory;

    /** @var \Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory */
    private $offerCollectionFactory;

    /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterfaceFactory */
    private $offerFactory;

    /** @var \Mirakl\Connector\Model\ResourceModel\Offer */
    private $offerResourceModel;

    /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface[] */
    private $offersList;

    /** @var \Magento\Framework\Api\SearchCriteria\CollectionProcessor */
    private $collectionProcessor;

    /**
     * OfferRepository constructor.
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterfaceFactory $offerSearchResultFactory
     * @param \Mirakl\Connector\Model\ResourceModel\Offer\CollectionFactory $offerCollectionFactory
     * @param \Magento\Framework\Api\SearchCriteria\CollectionProcessor $collectionProcessor
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterfaceFactory $offerFactory
     * @param \Mirakl\Connector\Model\ResourceModel\Offer $offerResourceModel
     */
    public function __construct(
        OfferSearchResultsInterfaceFactory $offerSearchResultFactory,
        OfferCollectionFactory $offerCollectionFactory,
        CollectionProcessor $collectionProcessor,
        OfferInterfaceFactory $offerFactory,
        OfferResourceModel $offerResourceModel
    ) {
        $this->offerSearchResultFactory = $offerSearchResultFactory;
        $this->offerCollectionFactory = $offerCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->offerFactory = $offerFactory;
        $this->offerResourceModel = $offerResourceModel;
    }

    /**
     * Get Offer Entity by Id
     *
     * @param int $offerId
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $offerId): OfferInterface
    {
        if (!isset($this->offersList[$offerId])) {
            /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer */
            $offer = $this->offerFactory->create();
            $this->offerResourceModel->load($offer, $offerId);
            if (!$offer->getId()) {
                throw new NoSuchEntityException(__('Unable to find Offer with ID "%1"', $offerId));
            }
            $this->offersList[$offerId] = $offer;
        }

        return $this->offersList[$offerId];
    }

    /**
     * Save Offer Entity
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(OfferInterface $offer): OfferInterface
    {
        $this->offerResourceModel->save($offer);

        return $offer;
    }

    /**
     * Delete Offer Entity
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer
     * @return bool
     * @throws \Exception
     */
    public function delete(OfferInterface $offer): bool
    {
        unset($this->offersList[$offer->getId()]);
        $this->offerResourceModel->delete($offer);

        return true;
    }

    /**
     * Delete Offer Entity by Id
     *
     * @param int $offerId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function deleteById(int $offerId): bool
    {
        $offer = $this->getById($offerId);

        return $this->delete($offer);
    }

    /**
     * Get Offers List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterface|\Magento\Framework\Api\SearchResults
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResults
    {
        /** @var \Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterface $searchResult */
        $searchResult = $this->offerSearchResultFactory->create();

        /** @var \Mirakl\Connector\Model\ResourceModel\Offer\Collection $collection */
        $collection = $this->offerCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var \Retailplace\MiraklConnector\Api\Data\OfferInterface[] $items */
        $items = $collection->getItems();

        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }
}
