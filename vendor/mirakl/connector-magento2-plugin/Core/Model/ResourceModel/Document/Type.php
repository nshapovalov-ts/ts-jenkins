<?php
namespace Mirakl\Core\Model\ResourceModel\Document;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Type extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return  void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('mirakl_document_type', 'id');
    }
}