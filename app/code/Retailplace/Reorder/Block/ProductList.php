<?php

/**
 * Retailplace_Reorder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Reorder\Block;

use Retailplace\ChannelPricing\Block\Product\ListProduct;

/**
 * Class ProductList
 */
class ProductList extends ListProduct
{
    /** @var string */
    const CSS_CLASS = "cssClass";

    /**
     * @return array|mixed|null
     */
    public function getCssClass()
    {
        return $this->getData(self::CSS_CLASS);
    }
}
