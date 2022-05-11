<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Page resource model
 */
class Page extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * Page constructor.
     * @param Context $context
     * @param \Magento\CatalogRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        Context $context,
        \Magento\CatalogRule\Model\RuleFactory  $ruleFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->dateTime = $dateTime;
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('magefan_cms_display_rule_page', 'page_id');
        $this->_isPkAutoIncrement = false;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $rulesData = $object->getData('magefan_cms_display_rules');
        if ($rulesData) {
            foreach ($rulesData as $key => $value) {
                if ($key == 'group_id' || $key == 'days_of_week') {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    $object->setData($key, $value);
                } elseif ($key == 'date_to' || $key == 'date_from') {
                    $value = !empty($rulesData[$key]) ? $rulesData[$key] : null;
                    $object->setData($key, $this->dateTime->formatDate($value));
                } else {
                    $object->setData($key, $value);
                }
            }
        }
        $conditions = json_encode($object->getData('rule'));
        $object->setData('conditions_serialized', $conditions);

        if ($object->getRule('conditions')) {
            $rule = $this->ruleFactory->create();
            $rule->loadPost(['conditions' => $object->getRule('conditions')]);
            $rule->beforeSave();
            $object->setData(
                'conditions_serialized',
                $rule->getConditionsSerialized()
            );
        }
        return parent::_beforeSave($object);
    }
}
