<?php
namespace Magecomp\Smspro\Model;

use Magento\Framework\Model\AbstractModel;

class Smspro extends AbstractModel
{
    protected function _construct()
    {
       $this->_init("Magecomp\Smspro\Model\ResourceModel\Smspro");
    }
}
