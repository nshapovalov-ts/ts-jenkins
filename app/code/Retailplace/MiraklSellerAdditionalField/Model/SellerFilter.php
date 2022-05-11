<?php
/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model;

/**
 * Class SellerFilter
 */
class SellerFilter
{
    /**
     * @var string[]
     */
    private $shopOptionIds;

    /**
     * @param string[] $shopOptionsIds
     */
    public function setFilteredShopOptionIds($shopOptionsIds)
    {
        $this->shopOptionIds = $shopOptionsIds;
    }

    /**
     * @return string[]
     */
    public function getFilteredShopOptionIds()
    {
        return $this->shopOptionIds;
    }
}
