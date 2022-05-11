<?php
namespace Mirakl\Core\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

class Shop extends Container
{
    /**
     * @return  void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Mirakl_Core';
        $this->_controller = 'adminhtml_shop';
        $this->_headerText = __('Shop List');
        parent::_construct();
        $this->removeButton('add');
        $this->addButton(
            'synchronize',
            [
                'label' => __('Synchronize Shops'),
                'class' => 'save primary',
                'onclick' => 'confirmSetLocation(\'' . __(
                    'Are you sure? This will update all modified shops since the last synchronization.'
                    ) . '\', \'' . $this->getUrl('*/sync/shop') . '\')'
            ]
        );
    }
}
