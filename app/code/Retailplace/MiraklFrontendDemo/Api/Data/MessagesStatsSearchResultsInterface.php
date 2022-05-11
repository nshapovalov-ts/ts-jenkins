<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface MessagesStatsSearchResultsInterface
 */
interface MessagesStatsSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Items list.
     *
     * @return MessagesStatsInterface[]
     */
    public function getItems(): array;

    /**
     * Set Items list.
     *
     * @param MessagesStatsInterface[] $items
     * @return MessagesStatsSearchResultsInterface
     */
    public function setItems(array $items): MessagesStatsSearchResultsInterface;

}
