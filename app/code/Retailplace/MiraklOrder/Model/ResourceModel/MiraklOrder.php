<?php

/**
 * Retailplace_MiraklOrder
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class MiraklOrder
 */
class MiraklOrder extends AbstractDb
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('mirakl_order', 'entity_id');
    }
}
