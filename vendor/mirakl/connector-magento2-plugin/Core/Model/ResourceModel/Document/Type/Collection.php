<?php
namespace Mirakl\Core\Model\ResourceModel\Document\Type;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Set resource model
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(\Mirakl\Core\Model\Document\Type::class, \Mirakl\Core\Model\ResourceModel\Document\Type::class);
    }
}
