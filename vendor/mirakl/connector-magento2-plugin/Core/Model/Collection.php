<?php
namespace Mirakl\Core\Model;

use Mirakl\Core\Domain\MiraklObject;

class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var string
     */
    protected $_itemObjectClass = MiraklObject::class;

    /**
     * @param   array   $items
     * @return  $this
     */
    public function setItems(array $items)
    {
        $this->_items = $items;

        return $this;
    }

    /**
     * @param   int $count
     * @return  $this
     */
    public function setTotalRecords($count)
    {
        $this->_totalRecords = $count;

        return $this;
    }
}
