<?php
namespace Magecomp\Smspro\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Smspro extends AbstractDb
{
    protected function _construct()
    {
    	$this->_init("sms_verify", "sms_verify_id");
    }
}
