<?php
namespace Mirakl\Core\Block\Adminhtml\Document;

use Magento\Backend\Block\Widget\Grid\Container;

class Type extends Container
{
    /**
     * @return  void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Mirakl_Core';
        $this->_controller = 'adminhtml_document_type';
        $this->_headerText = __('Document Type List');
        $this->_addButtonLabel = __('Add New Document Type');
        parent::_construct();
    }
}
