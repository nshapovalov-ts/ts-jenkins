<?php
namespace Mirakl\Core\Model\Shipping;

use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirakl\Core\Model\ResourceModel\Shipping\ZoneFactory as ZoneResourceFactory;
use Mirakl\Core\Model\Shipping\Zone\Rule as ZoneRule;
use Mirakl\Core\Model\Shipping\Zone\RuleFactory as ZoneRuleFactory;

/**
 * @method  string  getCode()
 * @method  $this   setCode(string $value)
 * @method  string  getEavOptionId()
 * @method  $this   setEavOptionId(int $optionId)
 * @method  int     getIsActive()
 * @method  $this   setIsActive(bool $value)
 * @method  string  getConditionsSerialized()
 * @method  $this   setConditionsSerialized(string $value)
 * @method  int     getSortOrder()
 * @method  $this   setSortOrder(int $value)
 * @method  $this   setStoreIds(array $value)
 */
class Zone extends AbstractModel
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'core_shipping_zone';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getShippingZone() in this case
     *
     * @var string
     */
    protected $_eventObject = 'shipping_zone';

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ZoneRule
     */
    protected $_rule;

    /**
     * @var ZoneRuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var ZoneResourceFactory
     */
    protected $_zoneResourceFactory;

    /**
     * @param   Context                     $context
     * @param   Registry                    $registry
     * @param   FormFactory                 $formFactory
     * @param   TimezoneInterface           $localeDate
     * @param   StoreManagerInterface       $storeManager
     * @param   ZoneRuleFactory             $zoneRuleFactory
     * @param   ZoneResourceFactory         $zoneResourceFactory
     * @param   AbstractResource|null       $resource
     * @param   AbstractDbCollection|null   $resourceCollection
     * @param   array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        StoreManagerInterface $storeManager,
        ZoneRuleFactory $zoneRuleFactory,
        ZoneResourceFactory $zoneResourceFactory,
        AbstractResource $resource = null,
        AbstractDbCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_storeManager = $storeManager;
        $this->_ruleFactory = $zoneRuleFactory;
        $this->_zoneResourceFactory = $zoneResourceFactory;
    }

    /**
     * Init resource model and id field
     *
     * @return  void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Mirakl\Core\Model\ResourceModel\Shipping\Zone::class);
        $this->setIdFieldName('id');
    }

    /**
     * @return  array
     */
    public function getConditions()
    {
        $conds = $this->_getData('conditions_serialized');
        if (is_string($conds)) {
            $conds = unserialize($conds);
        }

        return $conds;
    }

    /**
     * @return  ZoneRule
     */
    public function getRule()
    {
        if (null === $this->_rule) {
            $this->_rule = $this->_ruleFactory->create();
            $conds = $this->getConditions();
            if (!empty($conds)) {
                $this->_rule->loadPost(['conditions' => $conds]);
            }
        }

        return $this->_rule;
    }

    /**
     * Get associated store ids of current rule
     *
     * @return  array
     */
    public function getStoreIds()
    {
        if (!$this->hasData('store_ids')) {
            /** @var \Mirakl\Core\Model\ResourceModel\Shipping\Zone $resource */
            $resource = $this->_zoneResourceFactory->create();
            $storeIds = $resource->getStoreIds($this->getId());
            $this->setData('store_ids', (array) $storeIds);
        }

        return $this->_getData('store_ids');
    }
}
