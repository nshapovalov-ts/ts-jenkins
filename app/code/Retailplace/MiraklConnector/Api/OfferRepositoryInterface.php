<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Retailplace\MiraklConnector\Api\Data\OfferInterface;

/**
 * Interface OfferRepositoryInterface
 */
interface OfferRepositoryInterface
{
    /**
     * Get Offer Entity by Id
     *
     * @param int $offerId
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $offerId): OfferInterface;

    /**
     * Save Offer Entity
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(OfferInterface $offer): OfferInterface;

    /**
     * Delete Offer Entity
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface $offer
     * @return bool
     * @throws \Exception
     */
    public function delete(OfferInterface $offer): bool;

    /**
     * Delete Offer Entity by Id
     *
     * @param int $offerId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function deleteById(int $offerId): bool;

    /**
     * Get Offers List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterface|\Magento\Framework\Api\SearchResults
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResults;
}
