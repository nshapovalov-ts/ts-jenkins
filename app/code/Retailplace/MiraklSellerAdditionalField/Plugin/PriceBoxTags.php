<?php

/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Plugin;

use Magento\Framework\Pricing\Render\PriceBox;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

class PriceBoxTags
{
    /**
     * @var SellerFilter
     */
    private $sellerFilter;

    /**
     * @param SellerFilter $sellerFilter
     */
    public function __construct(
        SellerFilter $sellerFilter
    ) {
        $this->sellerFilter = $sellerFilter;
    }

    /**
     * @param PriceBox $subject
     * @param string $result
     * @return string
     */
    public function afterGetCacheKey(PriceBox $subject, string $result): string
    {
        $filteredShopOptionIds = $this->sellerFilter->getFilteredShopOptionIds();
        if (empty($filteredShopOptionIds)) {
            return $result;
        }

        return implode(
            '-',
            [
                $result,
                implode(',', $filteredShopOptionIds)
            ]
        );
    }
}
