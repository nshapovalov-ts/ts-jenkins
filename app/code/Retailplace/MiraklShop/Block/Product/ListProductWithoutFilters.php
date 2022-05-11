<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Block\Product;

use Magento\Catalog\Block\Product\ListProduct;

/**
 * Class ListProductWithoutFilters implements class for listing product without filters
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class ListProductWithoutFilters extends ListProduct
{
    /**
     * {@inheritdoc}
     */
    protected function _beforeToHtml(): ListProductWithoutFilters
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareSortableFieldsByCategory($category)
    {
        return $this;
    }
}
