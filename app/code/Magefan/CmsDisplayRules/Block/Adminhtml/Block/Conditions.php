<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Block\Adminhtml\Block;

/**
 * Class Conditions return block conditions form
 */
class Conditions extends \Magefan\CmsDisplayRules\Block\Adminhtml\Form\ConditionsForm
{
    /**
     * Conditions constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Rule\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\Cms\Model\BlockFactory $objectFactory
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magefan\CmsDisplayRules\Model\BlockRepository $blockRepository
     * @param \Magefan\CmsDisplayRules\Model\Block $customModel
     * @param string $formName
     * @param string $idField
     * @param string $jsFormObject
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
        \Magefan\CmsDisplayRules\Model\BlockRepository $blockRepository,
        \Magefan\CmsDisplayRules\Model\Block $customModel,
        $formName = 'cms_block_form',
        $idField = 'block_id',
        $jsFormObject = 'cms_block_formrule_conditions_fieldset_',
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $conditions,
            $rendererFieldset,
            $objectFactory,
            $ruleFactory,
            $blockRepository,
            $customModel,
            $formName,
            $idField,
            $jsFormObject,
            $data
        );
    }
}
