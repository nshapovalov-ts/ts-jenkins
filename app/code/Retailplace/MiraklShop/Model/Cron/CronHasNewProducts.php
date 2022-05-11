<?php

/**
 * Retailplace_MiraklShop
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklShop\Model\Cron;

use Retailplace\MiraklShop\Model\AttributeUpdater\HasNewProductsAttributeUpdater;

/**
 * Class CronHasNewProducts
 */
class CronHasNewProducts
{
    /** @var HasNewProductsAttributeUpdater */
    private $shopUpdater;

    /**
     * HasNewProducts Constructor
     *
     * @param HasNewProductsAttributeUpdater $shopUpdater
     */
    public function __construct(HasNewProductsAttributeUpdater $shopUpdater)
    {
        $this->shopUpdater = $shopUpdater;
    }

    /**
     * Run attribute update
     */
    public function run()
    {
        $this->shopUpdater->updateAll();
    }
}
