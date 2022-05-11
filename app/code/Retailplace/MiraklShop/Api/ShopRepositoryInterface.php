<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Retailplace\MiraklShop\Api\Data\ShopInterface;

/**
 * Interface ShopRepositoryInterface
 */
interface ShopRepositoryInterface
{
    /**
     * Get Shop Entity by ID
     *
     * @param int $shopId
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $shopId): ShopInterface;

    /**
     * Save Shop Entity
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterface $shop
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ShopInterface $shop): ShopInterface;

    /**
     * Delete Shop Entity
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterface $shop
     * @return bool
     * @throws \Exception
     */
    public function delete(ShopInterface $shop): bool;

    /**
     * Delete Shop Entity by ID
     *
     * @param int $shopId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function deleteById(int $shopId): bool;

    /**
     * Get Shops List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterface|\Magento\Framework\Api\SearchResults
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResults;
}
