<?php
namespace Mirakl\Core\Controller\Adminhtml\Document\Type;

use Mirakl\Core\Controller\Adminhtml\Document\Type;

class Index extends Type
{
    /**
     * @return  void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Document Type List'), __('Document Type'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Document Type List'));
        $this->_view->renderLayout();
    }
}
