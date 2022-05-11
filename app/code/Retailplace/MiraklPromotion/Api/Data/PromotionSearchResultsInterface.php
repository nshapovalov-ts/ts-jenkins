<?php

/**
 * Retailplace_MiraklPromotion
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklPromotion\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface PromotionSearchResultsInterface
 */
interface PromotionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Items Getter
     *
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[]
     */
    public function getItems(): array;

    /**
     * Items Setter
     *
     * @param \Retailplace\MiraklPromotion\Api\Data\PromotionInterface[] $items
     * @return \Retailplace\MiraklPromotion\Api\Data\PromotionSearchResultsInterface
     */
    public function setItems(array $items): PromotionSearchResultsInterface;
}
