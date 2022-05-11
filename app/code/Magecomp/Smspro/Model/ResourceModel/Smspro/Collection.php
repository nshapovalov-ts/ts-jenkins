<?php
namespace Magecomp\Smspro\Model\ResourceModel\Smspro;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	public function _construct()
	{
		$this->_init("Magecomp\Smspro\Model\Smspro", "Magecomp\Smspro\Model\ResourceModel\Smspro");
	}
}