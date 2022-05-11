<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Block\Adminhtml\Form;

use Magefan\CmsDisplayRules\Model\BlockRepository;

/**
 * Class ConditionsForm block
 */
class ConditionsForm extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Ui\Component\Layout\Tabs\TabInterface
{

    /**
     * Core registry
     *
     *
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Conditions
     */
    protected $conditions;

    /**
     * @var string
     */
    protected $nameInLayout = 'conditions_apply_to';

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $objectFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var object
     */
    protected $customModel;

    /**
     * @var string
     */
    protected $idField;

    /**
     * @var string
     */
    protected $formName;

    /**
     * @var BlockRepository
     */
    protected $cmsRepository;

    /**
     * @var null
     */
    protected $jsFormObject;
    /**
     * ConditionsForm constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\Cms\Model\BlockFactory $objectFactory
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param null $cmsRepository
     * @param null $customModel
     * @param null $formName
     * @param null $idField
     * @param null $jsFormObject
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Rule\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Magento\Cms\Model\BlockFactory $objectFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        $cmsRepository = null,
        $customModel = null,
        $formName = null,
        $idField = null,
        $jsFormObject = null,
        array $data = []
    ) {
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions = $conditions;
        $this->objectFactory = $objectFactory;
        $this->ruleFactory = $ruleFactory;
        $this->formName = $formName;
        $this->idField = $idField;
        $this->customModel = $customModel;
        $this->cmsRepository = $cmsRepository;
        $this->jsFormObject = $jsFormObject;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getTabClass()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabUrl()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Conditions');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Conditions');
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
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry(\Magento\SalesRule\Model\RegistryConstants::CURRENT_SALES_RULE);
        $form = $this->addTabToForm($this->customModel, 'conditions_fieldset', $this->formName);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Handles addition of conditions tab to supplied form.
     *
     * @param \Magento\SalesRule\Model\Rule $model
     * @param string $fieldsetId
     * @param string $formName
     * @return \Magento\Framework\Data\Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addTabToForm($model, $fieldsetId, $formName)
    {
        if (!$this->getRequest()->getParam('js_form_object')) {
            $this->getRequest()->setParam('js_form_object', $formName);
        }


        $id = $this->getRequest()->getParam($this->idField);
        if ($id) {
            $model = $this->cmsRepository->getById($id);
        }

        $rule = $this->ruleFactory->create();
        $rule->setData('conditions_serialized', $model->getData('conditions_serialized'));
        $model = $rule;

        $conditionsFieldSetId = $model->getConditionsFieldSetId($formName);
        $newChildUrl = $this->getUrl(
            'sales_rule/promo_quote/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => $formName]
        );

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');
        $renderer = $this->rendererFieldset->setTemplate(
            'Magento_CatalogRule::promo/fieldset.phtml'
        )->setNewChildUrl(
            $newChildUrl
        )->setFieldSetId(
            $conditionsFieldSetId
        );

        $fieldset = $form->addFieldset(
            $fieldsetId,
            [
                'legend' => __(
                    'Display Conditions (Display a CMS block/page only if the following conditions are met, 
                    leave blank to disable rule conditions)'
                )
            ]
        )->setRenderer(
            $renderer
        );

        $fieldset->addField(
            'conditions_serialized',
            'text',
            [
                'name'           => 'conditions_serialized',
                'label'          => __('Conditions'),
                'title'          => __('Conditions'),
                'required'       => true,
                'data-form-part' => $formName
            ]
        )->setRule(
            $model
        )->setRenderer(
            $this->conditions
        );
        $form->setValues($model->getData());
        $this->setConditionFormName($model->getConditions(), $formName);
        return $form;
    }

    /**
     * Handles addition of form name to condition and its conditions.
     *
     * @param \Magento\Rule\Model\Condition\AbstractCondition $conditions
     * @param string $formName
     * @return void
     */
    private function setConditionFormName(\Magento\Rule\Model\Condition\AbstractCondition $conditions, $formName)
    {
        $conditions->setFormName($formName);
        $conditions->setJsFormObject($this->jsFormObject);
        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            $conditions->getConditions()[0] = '';
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }
}
