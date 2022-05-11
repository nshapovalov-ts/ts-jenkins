<?php
namespace Mirakl\Core\Model\ResourceModel\Offer\State;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = \Mirakl\Core\Model\Offer\State::STATE_ID;

    /**
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Mirakl\Core\Model\Offer\State::class, \Mirakl\Core\Model\ResourceModel\Offer\State::class);
    }
}
