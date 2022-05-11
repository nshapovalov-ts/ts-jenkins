<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vdcstore\CategoryTree\Helper;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject;

class UpdateAttributeHelper extends AbstractHelper
{
    /**
     * Entity attribute values per backend table to delete
     *
     * @var array
     */
    protected $_attributeValuesToDelete = [];

    /**
     * Entity attribute values per backend table to save
     *
     * @var array
     */
    protected $_attributeValuesToSave = [];

    /**
     * Array of describe attribute backend tables
     * The table name as key
     *
     * @var array
     */
    protected static $_attributeBackendTables = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $_eavConfig;

    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    private $_attributeFactory;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $_localeFormat;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $_connection;

    /**
     * catalog_product entity type id
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * @var array
     */
    private $_attributes;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $productEavIndexerProcessor,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;
        $this->_attributeFactory = $attributeFactory;
        $this->_localeFormat = $localeFormat;
        $this->_connection = $resource->getConnection();
    }

    public function getAttribute($attributeCode)
    {
        if (!isset($this->_attributes[$attributeCode])) {
            $attribute = $this->_eavConfig->getAttribute(\Magento\Catalog\Model\Category::ENTITY, $attributeCode);
            $this->_attributes[$attributeCode] = $attribute;
        }
        return $this->_attributes[$attributeCode];
    }

    public function getDefaultStoreId()
    {
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    public function getLinkField()
    {
        return 'entity_id';
    }

    public function _prepareValueForSave($value, AbstractAttribute $attribute)
    {
        $type = $attribute->getBackendType();
        if (($type == 'int' || $type == 'decimal' || $type == 'datetime') && $value === '') {
            $value = null;
        } elseif ($type == 'decimal') {
            $value = $this->_localeFormat->getNumber($value);
        }
        $backendTable = $attribute->getBackendTable();
        if (!isset(self::$_attributeBackendTables[$backendTable])) {
            self::$_attributeBackendTables[$backendTable] = $this->_connection->describeTable($backendTable);
        }
        $describe = self::$_attributeBackendTables[$backendTable];
        return $this->_connection->prepareColumnValue($describe['value'], $value);
    }

    public function _prepareTableValueForSave($value, $type)
    {
        $type = strtolower($type);
        if ($type == 'decimal' || $type == 'numeric' || $type == 'float') {
            $value = $this->_localeFormat->getNumber($value);
        }
        return $value;
    }

    public function getStoreId($storeId = 0)
    {
        $hasSingleStore = $this->_storeManager->hasSingleStore();
        $storeId = $hasSingleStore ? $this->getDefaultStoreId() : (int) $this->_storeManager->getStore($storeId)->getId();
        return $storeId;
    }

    /**
     * Retrieve catalog_category entity type id
     *
     * @return int
     */
    public function getEntityTypeId()
    {
        if ($this->_entityTypeId === null) {
            $this->_entityTypeId = (int) $this->_eavConfig->getEntityType(\Magento\Catalog\Model\Category::ENTITY)
                ->getId();
        }
        return $this->_entityTypeId;
    }

    public function processUpdateAttributes($entityIds, $attrData, $storeId)
    {
        $object = new \Magento\Framework\DataObject();
        $object->setStoreId($storeId);

        $this->getConnection()->beginTransaction();
        try {
            foreach ($attrData as $attrCode => $value) {
                $attribute = $this->getAttribute($attrCode);
                if (!$attribute->getAttributeId()) {
                    continue;
                }

                $i = 0;
                foreach ($entityIds as $entityId) {
                    $i++;
                    $object->setId($entityId);
                    $object->setEntityId($entityId);
                    // collect data for save
                    $this->_saveAttributeValue($object, $attribute, $value);
                    // save collected data every 1000 rows
                    if ($i % 1000 == 0) {
                        $this->_processAttributeValues();
                    }
                }
                $this->_processAttributeValues();
            }
            $this->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }

        return $this;
    }

    protected function _processAttributeValues()
    {
        $connection = $this->getConnection();
        foreach ($this->_attributeValuesToSave as $table => $data) {
            $connection->insertOnDuplicate($table, $data, ['value']);
        }

        foreach ($this->_attributeValuesToDelete as $table => $valueIds) {
            $connection->delete($table, ['value_id IN (?)' => $valueIds]);
        }

        // reset data arrays
        $this->_attributeValuesToSave = [];
        $this->_attributeValuesToDelete = [];

        return $this;
    }

    public function getConnection()
    {
        return $this->_connection;
    }

    protected function _saveAttributeValue($object, $attribute, $value)
    {
        $connection = $this->getConnection();
        $storeId = (int) $this->_storeManager->getStore($object->getStoreId())->getId();
        $table = $attribute->getBackend()->getTable();

        $entityId = $object->getId();

        /**
         * If we work in single store mode all values should be saved just
         * for default store id
         * In this case we clear all not default values
         */
        if ($this->_storeManager->hasSingleStore()) {
            $storeId = $this->getDefaultStoreId();
            $connection->delete(
                $table,
                [
                    'attribute_id = ?'             => $attribute->getAttributeId(),
                    $this->getLinkField() . ' = ?' => $entityId,
                    'store_id <> ?'                => $storeId
                ]
            );
        }

        $data = new \Magento\Framework\DataObject(
            [
                'attribute_id'        => $attribute->getAttributeId(),
                'store_id'            => $storeId,
                $this->getLinkField() => $entityId,
                'value'               => $this->_prepareValueForSave($value, $attribute),
            ]
        );
        $bind = $this->_prepareDataForTable($data, $table);

        if ($attribute->isScopeStore()) {
            /**
             * Update attribute value for store
             */
            $this->_attributeValuesToSave[$table][] = $bind;
        } elseif ($attribute->isScopeWebsite() && $storeId != $this->getDefaultStoreId()) {
            /**
             * Update attribute value for website
             */
            $storeIds = $this->_storeManager->getStore($storeId)->getWebsite()->getStoreIds(true);
            foreach ($storeIds as $storeId) {
                $bind['store_id'] = (int) $storeId;
                $this->_attributeValuesToSave[$table][] = $bind;
            }
        } else {
            /**
             * Update global attribute value
             */
            $bind['store_id'] = $this->getDefaultStoreId();
            $this->_attributeValuesToSave[$table][] = $bind;
        }

        return $this;
    }

    protected function _prepareDataForTable(DataObject $object, $table)
    {
        $data = [];
        $fields = $this->getConnection()->describeTable($table);
        foreach (array_keys($fields) as $field) {
            if ($object->hasData($field)) {
                $fieldValue = $object->getData($field);
                if ($fieldValue instanceof \Zend_Db_Expr) {
                    $data[$field] = $fieldValue;
                } else {
                    if (null !== $fieldValue) {
                        $fieldValue = $this->_prepareTableValueForSave($fieldValue, $fields[$field]['DATA_TYPE']);
                        $data[$field] = $this->getConnection()->prepareColumnValue($fields[$field], $fieldValue);
                    } elseif (!empty($fields[$field]['NULLABLE'])) {
                        $data[$field] = null;
                    }
                }
            }
        }
        return $data;
    }

    public function updateCategoryAttributes($categoryIds, $attrData, $storeId = 0)
    {
        $this->processUpdateAttributes($categoryIds, $attrData, $storeId);
    }
}
