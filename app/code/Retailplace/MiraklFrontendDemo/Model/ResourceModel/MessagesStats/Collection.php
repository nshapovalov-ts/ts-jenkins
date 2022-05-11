<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Model\ResourceModel\MessagesStats;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection Class
 */
class Collection extends AbstractCollection
{

    protected $_idFieldName = 'id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init("Retailplace\MiraklFrontendDemo\Model\MessagesStats", "Retailplace\MiraklFrontendDemo\Model\ResourceModel\MessagesStats");
        $this->_map['fields']['entity_id'] = 'main_table.id';
    }
}
