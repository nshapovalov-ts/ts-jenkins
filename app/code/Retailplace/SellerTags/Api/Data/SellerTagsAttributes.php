<?php

/**
 * Retailplace_SellerTags
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerTags\Api\Data;

/**
 * Interface SellerTagsAttributes
 */
interface SellerTagsAttributes
{
    /** @var string */
    public const PRODUCT_OPEN_DURING_XMAS = 'open_during_xmas';
    public const PRODUCT_CLOSED_TO = 'closed_to';
    public const SHOP_OPEN_DURING_XMAS = 'open_during_xmas';
    public const SHOP_HOLIDAY_CLOSED_FROM = 'holiday_closed_from';
    public const SHOP_HOLIDAY_CLOSED_TO = 'holiday_closed_to';
    public const SHOP_LEADTIME_TO_SHIP = 'leadtime_to_ship';
    public const TS_FIRST_ORDER_DISCOUNT_AMOUNT = 'ts_first_order_discount';
    public const VIDEO_ID = 'video_id';
}
