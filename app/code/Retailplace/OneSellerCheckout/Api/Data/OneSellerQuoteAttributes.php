<?php

/**
 * Retailplace_OneSellerCheckout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\OneSellerCheckout\Api\Data;

/**
 * Interface OneSellerQuoteAttributes
 */
interface OneSellerQuoteAttributes
{
    /** @var string */
    public const QUOTE_PARENT_QUOTE_ID = 'parent_quote_id';
    public const QUOTE_SELLER_ID = 'seller_id';
}
