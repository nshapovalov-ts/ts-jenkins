<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Api\Data;

/**
 * Interface MiraklQuoteAttributes
 */
interface MiraklQuoteAttributes
{
    /** @var string */
    public const MIRAKL_QUOTE_ID = 'mirakl_quote_id';
    public const MIRAKL_QUOTE_ITEM_ID = 'mirakl_quote_item_id';
    public const MIRAKL_ORDER_QUOTE_ID = 'mirakl_quote_id';
    public const MIRAKL_ORDER_ITEM_ID = 'mirakl_quote_item_id';

    /** @var string */
    public const SHOP_MIN_QUOTE_REQUEST_AMOUNT = 'min_quote_request_amount';
}
