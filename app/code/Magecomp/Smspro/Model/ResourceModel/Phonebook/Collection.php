<?php
namespace Magecomp\Smspro\Model\ResourceModel\Phonebook;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'phonebook_id';
    protected function _construct()
    {
        $this->_init('Magecomp\Smspro\Model\Phonebook', 'Magecomp\Smspro\Model\ResourceModel\Phonebook');
    }
}