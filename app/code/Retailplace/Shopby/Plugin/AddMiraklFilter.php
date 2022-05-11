<?php

/**
 * Retailplace_Shopby
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Shopby\Plugin;

use Amasty\Shopby\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\Search\Response\QueryResponse;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;

/**
 * class AddMiraklFilter
 */
class AddMiraklFilter
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Collection $productCollection
     * @param string $field
     * @param QueryResponse|null $outerResponse
     *
     * @return array
     */
    public function beforeGetFacetedData(Collection $productCollection, string $field, QueryResponse $outerResponse = null): array
    {
        $this->helper->addShopIdsFilter($productCollection);

        return [$field, $outerResponse];
    }
}
