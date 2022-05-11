<?php
namespace Mirakl\Core\Model\ResourceModel\Shipping;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Zone extends \Magento\Rule\Model\ResourceModel\AbstractResource
{
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Store associated with shipping zone entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'store' => [
            'associations_table' => 'mirakl_shipping_zone_store',
            'rule_id_field' => 'zone_id',
            'entity_id_field' => 'store_id',
        ],
    ];

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var EavConfig
     */
    protected $_eavConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param   Context                 $context
     * @param   StoreManagerInterface   $storeManager
     * @param   EavConfig               $eavConfig
     * @param   EventManagerInterface   $eventManager
     * @param   LoggerInterface         $logger
     * @param   string                  $connectionName
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig,
        EventManagerInterface $eventManager,
        LoggerInterface $logger,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;
        $this->_eventManager = $eventManager;
        $this->_logger = $logger;
    }

    /**
     * Initialize main table and table id field
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_init('mirakl_shipping_zone', 'id');
    }

    /**
     * Add store ids to shipping zone data after load
     *
     * @param   AbstractModel   $object
     * @return  AbstractDb
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $object->setData('store_ids', (array) $this->getStoreIds($object->getId()));

        return parent::_afterLoad($object);
    }

    /**
     * Bind shipping zone to store(s)
     *
     * @param   AbstractModel   $object
     * @return  $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        if ($object->hasStoreIds()) {
            $storeIds = $object->getStoreIds();
            if (!is_array($storeIds)) {
                $storeIds = explode(',', (string) $storeIds);
            }
            $this->bindRuleToEntity($object->getId(), $storeIds, 'store');
        }

        parent::_afterSave($object);

        return $this;
    }

    /**
     * @param   AbstractModel   $zone
     * @return  $this
     */
    protected function _afterDelete(AbstractModel $zone)
    {
        $connection = $this->getConnection();
        $connection->delete(
            $this->getTable('mirakl_shipping_zone_store'),
            ['zone_id=?' => $zone->getId()]
        );

        parent::_afterDelete($zone);

        return $this;
    }

    /**
     * Retrieve store ids of specified shipping zone
     *
     * @param   int $zoneId
     * @return  array
     */
    public function getStoreIds($zoneId)
    {
        return $this->getAssociatedEntityIds($zoneId, 'store');
    }
}
