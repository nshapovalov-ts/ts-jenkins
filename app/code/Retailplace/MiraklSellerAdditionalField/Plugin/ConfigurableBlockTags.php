<?php

/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Plugin;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Retailplace\MiraklSellerAdditionalField\Model\SellerFilter;

class ConfigurableBlockTags
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
     * @param Configurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetCacheKey(Configurable $subject, string $result): string
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
