<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

use Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

class NewAction extends Zone
{
    /**
     * @return  void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
