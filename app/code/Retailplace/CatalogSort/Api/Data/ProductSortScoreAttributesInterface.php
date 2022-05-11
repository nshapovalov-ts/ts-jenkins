<?php

/**
 * Retailplace_CatalogSort
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CatalogSort\Api\Data;

/**
 * Interface ProductSortScoreAttributesInterface
 */
interface ProductSortScoreAttributesInterface
{
    /**
     * Attribute code
     *
     * @var string
     */
    const ATTRIBUTE_CODE = 'sort_score';

    /**
     * Attribute default label
     *
     * @var string
     */
    const ATTRIBUTE_DEFAULT_LABEL = 'Default';
}
