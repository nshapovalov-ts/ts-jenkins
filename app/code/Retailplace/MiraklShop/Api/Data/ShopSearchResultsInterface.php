<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface ShopSearchResultsInterface
 */
interface ShopSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Items Getter
     *
     * @return \Retailplace\MiraklShop\Api\Data\ShopInterface[]
     */
    public function getItems(): array;

    /**
     * Items Setter
     *
     * @param \Retailplace\MiraklShop\Api\Data\ShopInterface[] $items
     * @return \Retailplace\MiraklShop\Api\Data\ShopSearchResultsInterface
     */
    public function setItems(array $items): ShopSearchResultsInterface;
}
