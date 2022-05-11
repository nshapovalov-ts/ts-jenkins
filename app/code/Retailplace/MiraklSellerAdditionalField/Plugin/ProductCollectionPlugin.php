<?php

/**
 * Retailplace_MiraklSellerAdditionalField
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Ruslan Mukhatov <ruslan@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Retailplace\MiraklSellerAdditionalField\Helper\Data;

class ProductCollectionPlugin
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Collection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return null
     */
    public function beforeLoad(
        Collection $subject,
        $printQuery = false,
        $logQuery = false
    ) {
        $this->helper->addShopIdsFilter($subject);
        return null;
    }

    /**
     * @param Collection $subject
     * @return null
     */
    public function beforeGetSelectCountSql(Collection $subject)
    {
        $this->helper->addShopIdsFilter($subject);
        return null;
    }
}
