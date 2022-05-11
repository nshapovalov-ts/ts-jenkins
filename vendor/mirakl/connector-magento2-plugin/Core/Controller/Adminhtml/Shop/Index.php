<?php
namespace Mirakl\Core\Controller\Adminhtml\Shop;

class Index extends Shop
{
    /**
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Shop List'), __('Shop List'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shop List'));
        $this->_view->renderLayout();
    }
}
