<?php
namespace Mirakl\Core\Block\Adminhtml\Document\Type;

use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Apply" button
     * Add "Save and Continue" button
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Mirakl_Core';
        $this->_controller = 'adminhtml_document_type';

        parent::_construct();

        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Editing'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            10
       );
    }
}
