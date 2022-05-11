<?php
namespace Mirakl\Event\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Event extends AbstractDb
{
    /**
     * Initialize model and primary key field
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('mirakl_event', 'id');
    }

    /**
     * Perform actions before object save
     *
     * @param   AbstractModel   $object
     * @return  $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var \Mirakl\Event\Model\Event $object */

        $currentTime = date('Y-m-d H:i:s');
        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($currentTime);
        }
        $object->setUpdatedAt($currentTime);

        parent::_beforeSave($object);

        return $this;
    }

    /**
     * Deletes specified events from database
     *
     * @param   array   $ids
     * @return  bool|int
     */
    public function deleteIds(array $ids)
    {
        if (!empty($ids)) {
            return $this->getConnection()->delete($this->getMainTable(), ['id IN (?)' => $ids]);
        }

        return false;
    }

    /**
     * Truncate mirakl_event table
     */
    public function truncate()
    {
        $this->getConnection()->truncateTable($this->getMainTable());
    }
}
