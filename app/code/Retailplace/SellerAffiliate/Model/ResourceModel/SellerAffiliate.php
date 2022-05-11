<?php

/**
 * Retailplace_SellerAffiliate
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\SellerAffiliate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Retailplace\SellerAffiliate\Api\Data\SellerAffiliateInterface;

/**
 * Class SellerAffiliate implements resource model
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class SellerAffiliate extends AbstractDb
{
    /** @var string */
    public const TABLE_NAME = 'retailplace_shop_affiliate';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            self::TABLE_NAME,
            SellerAffiliateInterface::SELLERAFFILIATE_ID
        );
    }
}
