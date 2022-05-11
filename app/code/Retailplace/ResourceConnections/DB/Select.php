<?php
/**
 * Retailplace_ResourceConnection
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ResourceConnections\DB;

use Magento\Framework\DB\Select as MagentoSelect;

/**
 * Class Select
 * @package Magento\ResourceConnections\DB
 */
class Select extends MagentoSelect
{
    /**
     * Makes the query SELECT FOR UPDATE.
     *
     * @param bool $flag Whether or not the SELECT is FOR UPDATE (default true).
     * @return MagentoSelect
     */
    public function forUpdate($flag = true): MagentoSelect
    {
        $this->_adapter->setUseMasterConnection();
        return parent::forUpdate($flag);
    }
}
