<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model\ResourceModel\SellerAffiliate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Retailplace\SellerAffiliate\Model\ResourceModel\SellerAffiliate as ResourceModel;
use Retailplace\SellerAffiliate\Model\SellerAffiliate;

/**
 * Class Collection implements Collection model
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Collection extends AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SellerAffiliate::class,
            ResourceModel::class
        );
    }
}
