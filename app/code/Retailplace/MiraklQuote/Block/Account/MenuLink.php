<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Block\Account;

use Magento\Customer\Block\Account\SortLink;

/**
 * Class MenuLink
 */
class MenuLink extends SortLink
{
    /** @var string */
    public const QUOTES_ROUTE_NAME = 'quotes';

    /**
     * Check if menu item is active
     *
     * @return bool
     */
    public function getIsHighlighted(): bool
    {
        return $this->getRequest()->getRouteName() == self::QUOTES_ROUTE_NAME;
    }
}
