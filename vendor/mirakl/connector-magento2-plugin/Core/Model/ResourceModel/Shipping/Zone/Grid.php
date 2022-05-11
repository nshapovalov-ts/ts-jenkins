<?php
namespace Mirakl\Core\Model\ResourceModel\Shipping\Zone;

class Grid extends Collection
{
    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addStoresToResult();

        return $this;
    }
}
