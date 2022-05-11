<?php
namespace Mirakl\Connector\Block\Adminhtml\Offer\Grid;

class Container extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Mirakl_Connector';
        $this->_controller = 'adminhtml_offer';
        $this->_headerText = __('Offer List');

        parent::_construct();

        $this->removeButton('add');
        $this->addButton(
            'synchronize',
            [
                'label' => __('Synchronize Offers'),
                'class' => 'save primary',
                'onclick' => 'confirmSetLocation(\'' . __(
                    'Are you sure? This will update all modified offers since the last synchronization.'
                    ) . '\', \'' . $this->getUrl('*/sync/offer') . '\')'
            ]
        );
    }
}
