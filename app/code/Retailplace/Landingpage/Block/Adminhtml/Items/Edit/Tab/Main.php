<?php
/**
 * @category   Retailplace
 * @package    Retailplace_Landingpage
 * @author     ishvarmagento@gmail.com
 * @copyright  This file was generated by using Module Creator(http://code.vky.co.in/magento-2-module-creator/) provided by VKY <viky.031290@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Retailplace\Landingpage\Block\Adminhtml\Items\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{
    protected $_wysiwygConfig;
 
    public function __construct(
        \Magento\Backend\Block\Template\Context $context, 
        \Magento\Framework\Registry $registry, 
        \Magento\Framework\Data\FormFactory $formFactory,  
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig, 
        array $data = []
    ) 
    {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Item Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Item Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_retailplace_landingpage_items');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('item_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Item Information')]);
        if ($model->getId()) {
            $fieldset->addField('landingpage_id', 'hidden', ['name' => 'landingpage_id']);
        }

        $fieldset->addField(
            'url_key',
            'text',
            [
                'name' => 'url_key', 
                'label' => __('URL Key'), 
                'title' => __('URL Key'), 
                'class' => 'validate-identifier',
                'required' => true
            ]
        );
        $fieldset->addField(
            'page_title',
            'text',
            [
                'name' => 'page_title',
                'label' => __('Page Title'),
                'title' => __('Page Title')
            ]
        );
        /*$fieldset->addField(
            'image',
            'image',
            [
                'name' => 'image',
                'label' => __('Image'),
                'title' => __('Image'),
                'required'  => false
            ]
        );*/
        $fieldset->addField(
            'sectiononetitle',
            'text',
            ['name' => 'sectiononetitle', 'label' => __('Section One Title'), 'title' => __('Section One Title'), 'required' => false]
        );
        $fieldset->addField(
            'sectiononestatus',
            'select',
            ['name' => 'sectiononestatus', 'label' => __('Section One Status'), 'title' => __('Section One Status'),  'options'   => [0 => 'Disable', 1 => 'Enable'], 'required' => false]
        );
        $fieldset->addField(
            'sectiononecontent',
            'editor',
            [
                'name' => 'sectiononecontent',
                'label' => __('Section One Content'),
                'title' => __('Section One Content'),
                'style' => 'height:26em;',
                'required' => false,
                'config'    => $this->_wysiwygConfig->getConfig(),
                'wysiwyg' => true
            ]
        );
        
        $fieldset->addField(
            'sectiontwotitle',
            'text',
            ['name' => 'sectiontwotitle', 'label' => __('Section Two Title'), 'title' => __('Section Two Title'), 'required' => false]
        );
        $fieldset->addField(
            'sectiontwostatus',
            'select',
            ['name' => 'sectiontwostatus', 'label' => __('Section Two Status'), 'title' => __('Section Two Status'),  'options'   => [0 => 'Disable', 1 => 'Enable'], 'required' => false]
        );
        $fieldset->addField(
            'sectiontwocontent',
            'editor',
            [
                'name' => 'sectiontwocontent',
                'label' => __('Section Two Content'),
                'title' => __('Section Two Content'),
                'style' => 'height:26em;',
                'required' => false,
                'config'    => $this->_wysiwygConfig->getConfig(),
                'wysiwyg' => true
            ]
        );

        $fieldset->addField(
            'sectionthreetitle',
            'text',
            ['name' => 'sectionthreetitle', 'label' => __('Section Three Title'), 'title' => __('Section Three Title'), 'required' => false]
        );
        $fieldset->addField(
            'sectionthreestatus',
            'select',
            ['name' => 'sectionthreestatus', 'label' => __('Section Three Status'), 'title' => __('Section Three Status'),  'options'   => [0 => 'Disable', 1 => 'Enable'], 'required' => false]
        );
        $fieldset->addField(
            'sectionthreecontent',
            'editor',
            [
                'name' => 'sectionthreecontent',
                'label' => __('Section Three Content'),
                'title' => __('Section Three Content'),
                'style' => 'height:26em;',
                'required' => false,
                'config'    => $this->_wysiwygConfig->getConfig(),
                'wysiwyg' => true
            ]
        );

        $fieldset->addField(
            'sectionfourtitle',
            'text',
            ['name' => 'sectionfourtitle', 'label' => __('Section Four Title'), 'title' => __('Section Four Title'), 'required' => false]
        );
        $fieldset->addField(
            'sectionfourstatus',
            'select',
            ['name' => 'sectionfourstatus', 'label' => __('Section 4 Status'), 'title' => __('Section Four Status'),  'options'   => [0 => 'Disable', 1 => 'Enable'], 'required' => false]
        );
        $fieldset->addField(
            'sectionfourcontent',
            'editor',
            [
                'name' => 'sectionfourcontent',
                'label' => __('Section Four Content'),
                'title' => __('Section Four Content'),
                'style' => 'height:26em;',
                'required' => false,
                'config'    => $this->_wysiwygConfig->getConfig(),
                'wysiwyg' => true
            ]
        );

        $fieldset->addField(
            'sectionfivetitle',
            'text',
            ['name' => 'sectionfivetitle', 'label' => __('Section five Title'), 'title' => __('Section Five Title'), 'required' => false]
        );
        $fieldset->addField(
            'sectionfivestatus',
            'select',
            ['name' => 'sectionfivestatus', 'label' => __('Section five Status'), 'title' => __('Section Five Status'),  'options'   => [0 => 'Disable', 1 => 'Enable'], 'required' => false]
        );
        $fieldset->addField(
            'sectionfivecontent',
            'editor',
            [
                'name' => 'sectionfivecontent',
                'label' => __('Section Five Content'),
                'title' => __('Section Five Content'),
                'style' => 'height:26em;',
                'required' => false,
                'config'    => $this->_wysiwygConfig->getConfig(),
                'wysiwyg' => true
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }
}