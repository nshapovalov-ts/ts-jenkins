<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

namespace Retailplace\SellerAffiliate\Api;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Retailplace\SellerAffiliate\Api\Data\SellerAffiliateInterface;

/**
 * Interface SellerAffiliateRepositoryInterface
 */
interface SellerAffiliateRepositoryInterface
{
    /**
     * Get SellerAffiliate
     *
     * @param int $sellerAffiliateId
     * @return SellerAffiliateInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $sellerAffiliateId): SellerAffiliateInterface;

    /**
     * Save SellerAffiliate
     *
     * @param SellerAffiliateInterface $sellerAffiliate
     * @return SellerAffiliateInterface
     * @throws LocalizedException
     */
    public function save(SellerAffiliateInterface $sellerAffiliate): SellerAffiliateInterface;

    /**
     * Delete SellerAffiliate
     *
     * @param SellerAffiliateInterface $sellerAffiliate
     * @return bool
     * @throws LocalizedException
     */
    public function delete(SellerAffiliateInterface $sellerAffiliate): bool;

    /**
     * Delete SellerAffiliate by ID
     *
     * @param int $sellerAffiliateId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $sellerAffiliateId): bool;

    /**
     * Retrieve SellerAffiliate matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultInterface;
}
