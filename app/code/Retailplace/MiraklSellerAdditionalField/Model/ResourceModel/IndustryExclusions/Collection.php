<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'industryexclusions_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Retailplace\MiraklSellerAdditionalField\Model\IndustryExclusions::class,
            \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\IndustryExclusions::class
        );
    }
}

