<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Phonebook;

class Addrow extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $this->_forward('edit');
    }

    protected function _isAllowed()
    {
        return true;
    }


}