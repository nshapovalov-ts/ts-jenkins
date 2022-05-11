<?php
declare(strict_types=1);

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

namespace Retailplace\MiraklSeller\Block\Shop;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Exception\LocalizedException;

class Search extends Template
{
    /**
     * Get Search Result Count
     *
     * @return bool|int
     * @throws LocalizedException
     */
    public function getSearchResultCount()
    {
        $block = $this->getLayout()->getBlock('catalog.leftnav');
        if (empty($block)) {
            return false;
        }
        $layer = $block->getLayer();
        if (empty($layer)) {
            return false;
        }
        return $layer->getProductCollection()->count();
    }
}
