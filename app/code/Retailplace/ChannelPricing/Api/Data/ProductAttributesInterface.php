<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Api\Data;

/**
 * Interface ProductAttributesInterface
 */
interface ProductAttributesInterface
{
    /** @var string */
    public const AU_POST_EXCLUSIVE = 'au_post_exclusive';
    public const NLNA_EXCLUSIVE = 'nlna_exclusive';
}
