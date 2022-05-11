<?php
namespace Mirakl\Core\Model\Shipping\Zone\Condition;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Rule\Model\Condition\Context;
use Mirakl\Core\Model\Shipping\Zone\Condition\Address as AddressCondition;

class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var AddressCondition
     */
    protected $_addressCondition;

    /**
     * @param   Context                 $context
     * @param   EventManagerInterface   $eventManager
     * @param   AddressCondition        $addressCondition
     * @param   array                   $data
     */
    public function __construct(
        Context $context,
        EventManagerInterface $eventManager,
        AddressCondition $addressCondition,
        array $data = []
    ) {
        $this->_eventManager = $eventManager;
        $this->_addressCondition = $addressCondition;
        parent::__construct($context, $data);
        $this->setType(\Mirakl\Core\Model\Shipping\Zone\Condition\Combine::class);
    }

    /**
     * Get new child select options
     *
     * @return  array
     */
    public function getNewChildSelectOptions()
    {
        $addressAttributes = $this->_addressCondition->loadAttributeOptions()->getAttributeOption();
        $attributes = [];
        foreach ($addressAttributes as $code => $label) {
            $attributes[] = [
                'value' => \Mirakl\Core\Model\Shipping\Zone\Condition\Address::class . '|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
                    'label' => __('Conditions combination')
                ],
                ['label' => __('Address Condition'), 'value' => $attributes]
            ]
        );

        $additional = new DataObject();
        $this->_eventManager->dispatch('shipping_zone_rule_condition_combine', ['additional' => $additional]);
        $additionalConditions = $additional->getConditions();
        if ($additionalConditions) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }
}
