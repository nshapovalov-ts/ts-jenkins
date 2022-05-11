<?php
/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Plugin\Model;

use Mirasvit\Search\Model\Config as SearchConfig;
use Retailplace\Search\Model\SearchFilter;

/**
 * Class Config
 */
class Config
{
    /**
     * @var SearchFilter
     */
    private $searchFilter;

    /**
     * @param SearchFilter $searchFilter
     */
    public function __construct(
        SearchFilter $searchFilter
    ) {
        $this->searchFilter = $searchFilter;
    }

    /**
     * Get Wildcard Mode
     *
     * @param SearchConfig $subject
     * @param string $mode
     * @return string
     */
    public function afterGetWildcardMode(SearchConfig $subject, string $mode): string
    {
        $wildcardMode = $this->searchFilter->getModeWildcardAutocomplete();
        if ($wildcardMode) {
            $mode = $wildcardMode;

        }

        return $mode;
    }
}
