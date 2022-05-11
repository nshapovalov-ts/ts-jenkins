<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model\ResourceModel\ProductImport;

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
        $this->_init(
            \Retailplace\MiraklMcm\Model\ProductImport::class,
            \Retailplace\MiraklMcm\Model\ResourceModel\ProductImport::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.id';
    }
}
