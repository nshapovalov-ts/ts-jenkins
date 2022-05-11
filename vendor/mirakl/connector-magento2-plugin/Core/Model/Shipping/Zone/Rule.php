<?php
namespace Mirakl\Core\Model\Shipping\Zone;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mirakl\Core\Model\Shipping\Zone\Condition\CombineFactory as ZoneConditionCombineFactory;

class Rule extends \Magento\Rule\Model\AbstractModel
{
    /**
     * @var ZoneConditionCombineFactory
     */
    protected $conditionsFactory;

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   FormFactory                 $formFactory
     * @param   TimezoneInterface           $localeDate
     * @param   ZoneConditionCombineFactory $conditionsFactory
     * @param   AbstractResource|null       $resource
     * @param   AbstractDb|null             $resourceCollection
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        ZoneConditionCombineFactory $conditionsFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->conditionsFactory = $conditionsFactory;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionsInstance()
    {
        return $this->conditionsFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function getActionsInstance()
    {
        return null;
    }
}