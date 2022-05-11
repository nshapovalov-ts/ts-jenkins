<?php
namespace Mirakl\Core\Block\Adminhtml\Shipping\Zone\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions;
use Magento\Store\Model\System\Store as StoreSystem;

class Form extends Generic
{
    /**
     * @var Fieldset
     */
    protected $_rendererFieldset;

    /**
     * @var Conditions
     */
    protected $_conditions;

    /**
     * @var StoreSystem
     */
    protected $_systemStore;

    /**
     * @param   Context     $context
     * @param   Registry    $registry
     * @param   FormFactory $formFactory
     * @param   Conditions  $conditions
     * @param   Fieldset    $rendererFieldset
     * @param   StoreSystem $systemStore
     * @param   array       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Conditions $conditions,
        Fieldset $rendererFieldset,
        StoreSystem $systemStore,
        array $data = []
    ) {
        $this->_conditions = $conditions;
        $this->_rendererFieldset = $rendererFieldset;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init class
     *
     * @return  void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('shipping_zone_form');
        $this->setTitle(__('Shipping Zone Information'));
    }

    /**
     * @return  $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_shipping_zone');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setHtmlIdPrefix('zone_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Shipping Zone Information')]);

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField('code', 'text', [
            'name'     => 'code',
            'label'    => __('Code'),
            'title'    => __('Code'),
            'note'     => __('This code MUST match a shipping zone configured in Mirakl platform.'),
            'required' => true
        ]);

        $fieldset->addField('is_active', 'select', [
            'label'    => __('Status'),
            'title'    => __('Status'),
            'name'     => 'is_active',
            'required' => true,
            'options'  => ['1' => __('Active'), '0' => __('Inactive')]
        ]);

        $field = $fieldset->addField('store_ids', 'multiselect', [
            'name'     => 'store_ids[]',
            'label'    => __('Store View'),
            'title'    => __('Store View'),
            'required' => true,
            'values'   => $this->_systemStore->getStoreValuesForForm(false, true)
        ]);

        /** @var \Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element $renderer */
        $renderer = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
        );
        $field->setRenderer($renderer);

        $fieldset->addField('sort_order', 'text', [
            'name'  => 'sort_order',
            'label' => __('Priority'),
            'note'  => __('Low priority is used first.'),
        ]);

        // Address Conditions
        $renderer = $this->_rendererFieldset->setTemplate(
            'Mirakl_Core::shipping/zone/fieldset.phtml'
        )->setNewChildUrl(
            $this->getUrl('mirakl/shipping_zone/newConditionHtml/form/zone_conditions_fieldset')
        );

        $fieldset = $form
            ->addFieldset('condition_fieldset', ['legend' => __('Address Conditions (leave blank for all addresses)')])
            ->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            ['name' => 'conditions', 'label' => __('Conditions'), 'title' => __('Conditions'), 'required' => true]
        )->setRule(
            $model->getRule()
        )->setRenderer(
            $this->_conditions
        );

        $form->setValues(array_merge(['is_active' => '1'], $model->getData()));
        $form->setUseContainer(true);
        $this->setForm($form);

        parent::_prepareForm();

        return $this;
    }
}
