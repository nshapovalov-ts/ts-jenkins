<?php

/**
 * Retailplace_MiraklConnector
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklConnector\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface OfferSearchResultsInterface
 */
interface OfferSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Items Getter
     *
     * @return \Retailplace\MiraklConnector\Api\Data\OfferInterface[]
     */
    public function getItems(): array;

    /**
     * Items Setter
     *
     * @param \Retailplace\MiraklConnector\Api\Data\OfferInterface[] $items
     * @return \Retailplace\MiraklConnector\Api\Data\OfferSearchResultsInterface
     */
    public function setItems(array $items): OfferSearchResultsInterface;
}
