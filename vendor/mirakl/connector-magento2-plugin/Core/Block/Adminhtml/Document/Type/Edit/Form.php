<?php
namespace Mirakl\Core\Block\Adminhtml\Document\Type\Edit;

use Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{
    /**
     * Init class
     *
     * @return  void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('document_type_form');
        $this->setTitle(__('Document Type Information'));
    }

    /**
     * @return  $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_document_type');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setHtmlIdPrefix('type_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Document Type Information')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField('label', 'text', [
            'name'     => 'label',
            'label'    => __('Label'),
            'title'    => __('Label'),
            'note'     => __('This label is associated with a Document Type code configured in Mirakl platform.'),
            'required' => true
        ]);

        $fieldset->addField('code', 'text', [
            'name'     => 'code',
            'label'    => __('Code'),
            'title'    => __('Code'),
            'note'     => __('This code MUST match a Document Type configured in Mirakl platform.'),
            'required' => true
        ]);

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        parent::_prepareForm();

        return $this;
    }
}
