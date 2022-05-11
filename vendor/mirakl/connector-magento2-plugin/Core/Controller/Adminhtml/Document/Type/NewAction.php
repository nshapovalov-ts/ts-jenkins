<?php
namespace Mirakl\Core\Controller\Adminhtml\Document\Type;

use Mirakl\Core\Controller\Adminhtml\Document\Type;

class NewAction extends Type
{
    /**
     * @return  void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
