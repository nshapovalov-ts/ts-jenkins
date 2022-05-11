<?php
namespace Mirakl\Core\Block\Adminhtml\Shipping;

use Magento\Backend\Block\Widget\Grid\Container;

class Zone extends Container
{
    /**
     * @return  void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Mirakl_Core';
        $this->_controller = 'adminhtml_shipping_zone';
        $this->_headerText = __('Shipping Zone List');
        $this->_addButtonLabel = __('Add New Shipping Zone');
        parent::_construct();
    }
}
