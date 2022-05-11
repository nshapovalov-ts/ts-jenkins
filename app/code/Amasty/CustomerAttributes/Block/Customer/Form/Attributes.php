<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Block\Customer\Form;

use Amasty\CustomerAttributes\Model\ResourceModel\RelationDetails\CollectionFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Customer\Model\AttributeMetadataDataProvider;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Block\Adminhtml\Form;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObjectFactory;
use Amasty\CustomerAttributes\Block\Widget\Form\Renderer\Element;
use Amasty\CustomerAttributes\Block\Widget\Form\Renderer\Fieldset;
use Magento\Eav\Model\Entity\Attribute;

class Attributes extends Form
{
    /**
     * @var AttributeMetadataDataProvider
     */
    private $attributeMetadataDataProvider;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var  array
     */
    private $_customerData;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionFactory
     */
    private $relationCollectionFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var Element
     */
    private $elementRenderer;

    /**
     * @var Fieldset
     */
    private $fieldsetRenderer;

    /**
     * Attributes constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param AttributeMetadataDataProvider $attributeMetadataDataProvider
     * @param Fieldset $fieldsetRenderer
     * @param Element $elementRenderer
     * @param ObjectManagerInterface $objectManager
     * @param CollectionFactory $relationDetailsCollectionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param Session $session
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        AttributeMetadataDataProvider $attributeMetadataDataProvider,
        Fieldset $fieldsetRenderer,
        Element $elementRenderer,
        ObjectManagerInterface $objectManager,
        CollectionFactory $relationDetailsCollectionFactory,
        DataObjectFactory $dataObjectFactory,
        \Magento\Customer\Model\SessionFactory $session,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->attributeMetadataDataProvider = $attributeMetadataDataProvider;
        $this->fieldsetRenderer = $fieldsetRenderer;
        $this->elementRenderer = $elementRenderer;
        $this->objectManager = $objectManager;
        $this->storeManager = $context->getStoreManager();
        $this->relationCollectionFactory = $relationDetailsCollectionFactory;
        $this->session = $session->create();
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Check whether attribute is visible
     *
     * @param Attribute $attribute
     *
     * @return bool
     */
    public function isAttributeVisible(Attribute $attribute)
    {
        $blockName = $this->getNameInLayout();
        if ($blockName == 'attribute_customer_register') {
            $check = $attribute->getData('on_registration') == '1';
        } else {
            $check = $attribute->getData('is_visible_on_front') == '1'
                && (!($attribute->getAccountFilled() == '1'
                        && array_key_exists($attribute->getAttributeCode(), $this->getCustomerData())
                    )
                    || $attribute->getAccountFilled() == '0'
                );
        }

        $store = $this->storeManager->getStore()->getId();
        $stores = $attribute->getStoreIds();
        $stores = explode(',', $stores);

        return !(!$attribute || $attribute->hasIsVisible() && !$attribute->getIsVisible())
            && $check
            && in_array($store, $stores);
    }

    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        if ($this->getNameInLayout() == 'attribute_customer_edit') {
            $type = 'customer_account_edit';
        } else {
            $type = 'customer_attributes_registration';
        }

        $attributes = $this->attributeMetadataDataProvider->loadAttributesCollection(
            'customer',
            $type
        );

        if (!$attributes || !$attributes->getSize()) {
            return;
        }
        $fieldset = $form->addFieldset(
            'group-fields-customer-attributes',
            [
                'class' => 'user-defined',
                'legend' => '<span class="number-2">2</span>  '.__('Personal details')//Additional Settings
            ]
        );
        $fieldset->setRenderer($this->fieldsetRenderer);

        $this->_setFieldset($attributes, $fieldset, ['gallery']);
        $this->prepareRelations($attributes, $fieldset);

        $this->setForm($form);
    }

    /**
     * @inheritdoc
     */
    protected function _initFormValues()
    {
        if ($form = $this->getForm()) {
            if ($this->getCustomerData()) {
                $form->addValues($this->getCustomerData());
            }
            /** @var \Magento\Customer\Block\Form\Register $registerForm */
            $registerForm = $this->getLayout()->getBlock('customer_form_register');
            if (is_object($registerForm) && $registerForm->getFormData() instanceof \Magento\Framework\DataObject) {
                $form->addValues($registerForm->getFormData()->getData());
            }
        }

        return parent::_initFormValues();
    }

    /**
     * Set Fieldset to Form
     *
     * @param array|\Magento\Customer\Model\ResourceModel\Form\Attribute\Collection $attributes
     * $attributes - attributes that are to be added
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param array $exclude attributes that should be skipped
     *
     * @return void
     */
    protected function _setFieldset($attributes, $fieldset, $exclude = [])
    {
        $this->_addElementTypes($fieldset);

        foreach ($attributes as $attribute) {
            /** @var $attribute \Magento\Eav\Model\Entity\Attribute */
            if (!$this->isAttributeVisible($attribute)) {
                continue;
            }
            $attribute->setStoreId($this->storeManager->getStore()->getId());
            if (($inputType = $attribute->getFrontend()->getInputType())
                && !in_array(
                    $attribute->getAttributeCode(),
                    $exclude
                )
                && ('media_image' != $inputType || $attribute->getAttributeCode() == 'image')
            ) {
                $typeInternal = $attribute->getTypeInternal();

                $inputTypes = [
                    'statictext' => 'note',
                    'selectgroup' => 'select'
                ];

                if ($typeInternal) {
                    $inputType = isset($inputTypes[$typeInternal])
                        ? $inputTypes[$typeInternal] : $typeInternal;
                }
                $rendererClass = $attribute->getFrontend()->getInputRendererClass();
                $fieldType = 'Amasty\CustomerAttributes\Block\Data\Form\Element\\' . ucfirst($inputType);
                if (!empty($rendererClass)) {
                    $fieldType = $inputType . '_' . $attribute->getAttributeCode();
                    $fieldset->addType($fieldType, $rendererClass);
                }

                $data = [
                    'name' => $attribute->getAttributeCode(),
                    'label' => $attribute->getStoreLabel(),
                    'class' => $attribute->getFrontend()->getClass(),
                    'required' => $attribute->getIsRequired() || $attribute->getRequiredOnFront(),
                    'note' => $attribute->getNote()
                ];
                if ($typeInternal == 'selectgroup'
                    && !$this->_scopeConfig->getValue('amcustomerattr/general/allow_change_group')
                ) {
                    $data['disabled'] = 'disabled';
                }

                $element = $fieldset->addField(
                    $attribute->getAttributeCode(),
                    $fieldType,
                    $data
                )->setEntityAttribute(
                    $attribute
                );

                $element->setValue($attribute->getDefaultValue());

                $element->setRenderer($this->elementRenderer);

                $element->setAfterElementHtml($this->_getAdditionalElementHtml($element));

                /* add options / date format */
                $this->_applyTypeSpecificConfig($inputType, $element, $attribute);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _applyTypeSpecificConfig($inputType, $element, Attribute $attribute)
    {
        switch ($inputType) {
            case 'selectimg':
                $element->addElementValues($attribute->getSource()->getAllOptions(false, false));
                break;
            case 'select':
                $element->addElementValues($attribute->getSource()->getAllOptions(true, false));
                break;
            case 'multiselectimg':
            case 'multiselect':
                $element->addElementValues($attribute->getSource()->getAllOptions(false, false));
                $element->setCanBeEmpty(true);
                break;
            case 'date':
                $element->setDateFormat($this->_localeDate->getDateFormatWithLongYear());
                break;
            case 'multiline':
                $element->setLineCount($attribute->getMultilineCount());
                break;
            default:
                break;
        }
    }

    /**
     * @param \Magento\Customer\Model\ResourceModel\Form\Attribute\Collection $attributes
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     */
    protected function prepareRelations($attributes, $fieldset)
    {
        $attributeIds = $attributes->getColumnValues('attribute_id');
        if (empty($attributeIds)) {
            return;
        }
        $dependentCollection = $this->relationCollectionFactory->create()
            ->addFieldToFilter('main_table.attribute_id', ['in' => $attributeIds])
            ->joinDependAttributeCode();

        $depends = [];
        /** @var \Amasty\CustomerAttributes\Api\Data\RelationDetailInterface $relationDetail */
        foreach ($dependentCollection as $relationDetail) {
            $depends[] = [
                'parent_attribute_id' => $relationDetail->getAttributeId(),
                'parent_attribute_code' => $relationDetail->getData('parent_attribute_code'),
                'parent_option_id' => $relationDetail->getOptionId(),
                'depend_attribute_id' => $relationDetail->getDependentAttributeId(),
                'depend_attribute_code' => $relationDetail->getData('dependent_attribute_code')
            ];
        }
        if (!empty($depends)) {
            $fieldset->setData('depends', $depends);
        }
    }

    /**
     * Retrieve additional element types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        $result = [
            'price' => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Price',
            'weight' => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Weight',
            'gallery' => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Gallery',
            'image' => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Image',
            'boolean' => 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Boolean',
            'textarea' => 'Magento\Catalog\Block\Adminhtml\Helper\Form\Wysiwyg',
        ];

        $response = $this->dataObjectFactory->create();
        $response->setTypes([]);
        $this->_eventManager->dispatch('adminhtml_catalog_product_edit_element_types', ['response' => $response]);

        foreach ($response->getTypes() as $typeName => $typeClass) {
            $result[$typeName] = $typeClass;
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getCustomerData()
    {
        if (!isset($this->_customerData)) {
            $this->_customerData = [];
            if ($this->session->isLoggedIn()) {
                $this->_customerData = $this->session->getCustomer()->getData();
            }
        }

        return $this->_customerData;
    }
}
