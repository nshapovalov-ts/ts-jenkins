<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Retailplace\MiraklOrder\Model\MiraklOrder;
use Retailplace\MiraklOrder\Model\ResourceModel\MiraklOrder as OrderResource;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'entity_id';

    /** @var string */
    protected $_eventPrefix = 'mirakl_order_collection';

    /** @var string */
    protected $_eventObject = 'mirakl_order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(MiraklOrder::class, OrderResource::class);
    }
}
