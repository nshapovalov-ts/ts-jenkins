<?php

/**
 * Retailplace_CatalogSort
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CatalogSort\Model\Cron;

use Retailplace\CatalogSort\Model\SortScoreCalculatedManagement;

/**
 * Class UpdateSortScore
 */
class UpdateSortScore
{
    /**
     * @var SortScoreCalculatedManagement
     */
    private $sortScoreCalculatedManagement;

    /**
     * UpdateSortScore constructor.
     *
     * @param SortScoreCalculatedManagement $sortScoreCalculatedManagement
     */
    public function __construct(
        SortScoreCalculatedManagement $sortScoreCalculatedManagement
    ) {
        $this->sortScoreCalculatedManagement = $sortScoreCalculatedManagement;
    }

    /**
     * Run
     */
    public function run()
    {
        $this->sortScoreCalculatedManagement->updateSortScore();
    }
}
