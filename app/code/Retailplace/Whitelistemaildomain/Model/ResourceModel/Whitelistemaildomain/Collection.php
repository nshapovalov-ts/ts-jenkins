<?php

namespace Retailplace\Whitelistemaildomain\Model\ResourceModel\Whitelistemaildomain;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Retailplace\Whitelistemaildomain\Model\Whitelistemaildomain', 'Retailplace\Whitelistemaildomain\Model\ResourceModel\Whitelistemaildomain');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }

}
?>