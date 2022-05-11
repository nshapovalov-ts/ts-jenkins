<?php
namespace Magecomp\Smspro\Model\ResourceModel;

class Phonebook extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('phonebook','phonebook_id');
    }
}