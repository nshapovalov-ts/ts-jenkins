<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Plugin;

use Magento\Catalog\Pricing\Render\FinalPriceBox as MagentoFinalPriceBox;

/**
 * Class FinalPriceBox
 */
class FinalPriceBox
{
    /**
     * Add Zone to Cache Key for the Final Price Box Block
     *
     * @see \Magento\Catalog\Pricing\Render\FinalPriceBox::getCacheKey()
     * @param \Magento\Catalog\Pricing\Render\FinalPriceBox $subject
     * @param string $result
     * @return string
     */
    public function afterGetCacheKey(MagentoFinalPriceBox $subject, string $result): string
    {
        $result .= $subject->getZone();

        return $result;
    }
}
