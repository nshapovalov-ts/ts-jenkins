<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'channelexclusions_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Retailplace\MiraklSellerAdditionalField\Model\ChannelExclusions::class,
            \Retailplace\MiraklSellerAdditionalField\Model\ResourceModel\ChannelExclusions::class
        );
    }
}

