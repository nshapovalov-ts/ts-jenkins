<?php
namespace Mirakl\Core\Block\Adminhtml\Offer;

use Magento\Backend\Block\Widget\Grid\Container;

class State extends Container
{
    /**
     * @return  void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Mirakl_Core';
        $this->_controller = 'adminhtml_offer_state';
        $this->_headerText = __('Offer Condition List');
        parent::_construct();
        $this->removeButton('add');
        $this->addButton(
            'synchronize',
            [
                'label' => __('Synchronize Offer Conditions'),
                'class' => 'save primary',
                'onclick' => 'confirmSetLocation(\'' . __(
                    'Are you sure? This will update all offer conditions.'
                    ) . '\', \'' . $this->getUrl('*/sync/state') . '\')'
            ]
        );
    }
}
