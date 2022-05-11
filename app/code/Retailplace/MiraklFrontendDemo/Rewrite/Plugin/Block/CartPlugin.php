<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Plugin\Block;

use Magento\Checkout\Block\Cart;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Class CartPlugin
 */
class CartPlugin extends \Mirakl\FrontendDemo\Plugin\Block\CartPlugin
{
    /**
     * Disable dynamic adding of Cart Item Shipping Block since it was added via Layout
     *
     * @param   Cart        $subject
     * @param   \Closure    $proceed
     * @param   QuoteItem   $item
     * @return  string
     */
    public function aroundGetItemHtml(Cart $subject, \Closure $proceed, QuoteItem $item)
    {
        return $proceed($item);
    }
}
