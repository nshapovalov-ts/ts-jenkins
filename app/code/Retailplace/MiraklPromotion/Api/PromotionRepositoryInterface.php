<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Mirakl\MMP\FrontOperator\Domain\Promotion\Promotion as MiraklPromotion;
use Retailplace\MiraklPromotion\Api\Data\PromotionInterface;
use Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface;

/**
 * Interface PromotionRepositoryInterface
 */
interface PromotionRepositoryInterface
{
    /**
     * Get Promotion by Id
     *
     * @param int $promotionId
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $promotionId): PromotionInterface;

    /**
     * Save Promotion
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterface $promotion
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(PromotionInterface $promotion): PromotionInterface;

    /**
     * Delete Promotion
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterface $promotion
     * @return bool
     * @throws \Exception
     */
    public function delete(PromotionInterface $promotion): bool;

    /**
     * Delete Promotion by Id
     *
     * @param int $promotionId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function deleteById(int $promotionId): bool;

    /**
     * Convert Mirakl Promotion to Retailplace Promotion
     *
     * @param \Mirakl\MMP\FrontOperator\Domain\Promotion\Promotion $miraklPromotion
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface
     */
    public function convertMiraklPromotion(MiraklPromotion $miraklPromotion): PromotionInterface;

    /**
     * Generate Unique ID for Promotion
     *
     * @param int $shopId
     * @param string $promotionInternalId
     * @return string
     */
    public function generatePromotionUniqueId(int $shopId, string $promotionInternalId): string;

    /**
     * Get Promotions List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResults;

    /**
     * Get Active Promotions by Shops List
     *
     * @param array $shopIds
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface
     */
    public function getActiveByShops(array $shopIds): SearchResults;
}
