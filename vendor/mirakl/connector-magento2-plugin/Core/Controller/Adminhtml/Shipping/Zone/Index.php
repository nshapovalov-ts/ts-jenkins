<?php
namespace Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

use Mirakl\Core\Controller\Adminhtml\Shipping\Zone;

class Index extends Zone
{
    /**
     * @return  void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Shipping Zone List'), __('Shipping Zone List'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipping Zone List'));
        $this->_view->renderLayout();
    }
}
