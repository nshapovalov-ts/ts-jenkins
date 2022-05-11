<?php

/**
 * Retailplace_SellerTags
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerTags\Model\Cron;

use Retailplace\SellerTags\Model\Updater\ClosedUntil;

/**
 * Class UpdateHolidayClosed
 */
class UpdateHolidayClosed
{
    /** @var \Retailplace\SellerTags\Model\Updater\ClosedUntil */
    private $attributeUpdater;

    /**
     * UpdateHolidayClosed Constructor
     *
     * @param \Retailplace\SellerTags\Model\Updater\ClosedUntil $attributeUpdater
     */
    public function __construct(
        ClosedUntil $attributeUpdater
    ) {
        $this->attributeUpdater = $attributeUpdater;
    }

    /**
     * Run Promotions Import
     */
    public function run()
    {
        $this->attributeUpdater->run();
    }
}
