<?php

namespace Magecomp\Smspro\Block\Adminhtml\Phonebook\Edit\Tab;

use Psr\Log\LoggerInterface;


class Phonebook extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_systemStore;
    protected $emailcollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        LoggerInterface $logger,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $emailcollection,
        array $data = []
    )
    {
        $this->logger = $logger;
        $this->_systemStore = $systemStore;
        $this->emailcollection = $emailcollection;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getTabLabel()
    {
        return __('Entry Settings');
    }

    public function getTabTitle()
    {
        return __('Entry Settings');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('phonebook');

        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('phonebook_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Entry Information')]);

        if ($model->getId()) {
            $fieldset->addField('phonebook_id', 'hidden', ['name' => 'phonebook_id']);
        }

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true,
            ]
        );
        $fieldset->addField(
            'mobile',
            'text',
            [
                'name' => 'mobile',
                'label' => __('Mobile'),
                'title' => __('Mobile'),
                'class' => 'validate-number validate-zero-or-greater',
                'required' => true,
            ]
        );


        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _isAllowedAction( $resourceId )
    {
        return true;
    }
}