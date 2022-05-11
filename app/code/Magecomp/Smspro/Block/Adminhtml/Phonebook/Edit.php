<?php
namespace Magecomp\Smspro\Block\Adminhtml\Phonebook;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,

        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('extension')->getId()) {
            return __("Edit Entry '%1'", $this->escapeHtml($this->_coreRegistry->registry('extension')->getTitle()));
        } else {
            return __('New Entry');
        }
    }

    public function _getSaveAndContinueUrl()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->getUrl('*/*/save', ['_current' => true, 'store' => $storeId, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    public function getSaveUrl()
    {
        $storeId = $this->getRequest()->getParam('store');
        return $this->getUrl('*/*/save', ['_current' => true, 'store' => $storeId, 'active_tab' => '{{tab_id}}']);
    }

    protected function _construct()
    {
        $this->_objectId = 'phonebook_id';
        $this->_blockGroup = 'Magecomp_Smspro';
        $this->_controller = 'adminhtml_phonebook';

        parent::_construct();

        if ($this->_isAllowedAction('Magecomp_Smspro::phonebook')) {
            $this->buttonList->update('save', 'label', __('Save Entry'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                        'form-role' => 'save',
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }


        if ($this->_isAllowedAction('Magecomp_Smspro::phonebook')) {
            $this->addButton(
                'delete',
                [
                    'label' => __('Delete Entry'),
                    'onclick' => 'deleteConfirm(' . json_encode(__('Are you sure you want to do this?'))
                        . ','
                        . json_encode($this->getDeleteUrl()
                        )
                        . ')',
                    'class' => 'scalable delete',
                    'level' => -1
                ]
            );
        } else {
            $this->buttonList->remove('delete');
        }
    }

    protected function _isAllowedAction( $resourceId )
    {
        return true;
    }

    public function getDeleteUrl( array $args = [] )
    {
        return $this->getUrl('*/*/delete', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            };
        ";
        return parent::_prepareLayout();
    }

}
