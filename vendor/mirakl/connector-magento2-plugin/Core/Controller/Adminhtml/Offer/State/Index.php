<?php
namespace Mirakl\Core\Controller\Adminhtml\Offer\State;

use Mirakl\Core\Controller\Adminhtml\Offer\State;

class Index extends State
{
    /**
     * @return  void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Offer Condition List'), __('Offer Condition List'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Offer Condition List'));
        $this->_view->renderLayout();
    }
}
