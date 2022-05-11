<?php
namespace Mirakl\Event\Model;

use Magento\Framework\Model\AbstractModel;
use Mirakl\Catalog\Helper\Config as CatalogConfigHelper;
use Mirakl\Mci\Helper\Config as MciConfigHelper;
use Mirakl\Mcm\Helper\Config as McmConfigHelper;

/**
 * @method  int     getAction()
 * @method  $this   setAction(int $action)
 * @method  string  getCode()
 * @method  $this   setCode(string $code)
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  $this   setCsvData(string $csvData)
 * @method  string  getImportId()
 * @method  $this   setImportId(int $importId)
 * @method  string  getLine()
 * @method  $this   setLine(int $line)
 * @method  string  getMessage()
 * @method  $this   setMessage(string $message)
 * @method  string  getProcessId()
 * @method  $this   setProcessId(int $processId)
 * @method  string  getStatus()
 * @method  $this   setStatus(string $status)
 * @method  int     getType()
 * @method  $this   setType(int $type)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 */
class Event extends AbstractModel
{
    const STATUS_WAITING        = 'waiting';
    const STATUS_PROCESSING     = 'processing';
    const STATUS_SENT           = 'sent';
    const STATUS_SUCCESS        = 'success';
    const STATUS_INTERNAL_ERROR = 'internal_error';
    const STATUS_MIRAKL_ERROR   = 'mirakl_error';

    const TYPE_VL01 = 1;
    const TYPE_H01  = 2;
    const TYPE_PM01 = 3;
    const TYPE_CA01 = 4;
    const TYPE_P21  = 5;
    const TYPE_CM21 = 6;

    const ACTION_PREPARE = 0;
    const ACTION_UPDATE  = 1;
    const ACTION_DELETE  = 2;

    /**
     * @var string
     */
    protected $_eventPrefix = 'mirakl_event';

    /**
     * @var string
     */
    protected $_eventObject = 'mirakl_event';

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var array
     */
    protected static $_types = [
        self::TYPE_VL01 => 'Value Lists Synchronization (VL01)',
        self::TYPE_H01  => 'Catalog Categories Synchronization (H01)',
        self::TYPE_PM01 => 'Attributes Synchronization (PM01)',
        self::TYPE_CA01 => 'Marketplace Categories Synchronization (CA01)',
        self::TYPE_P21  => 'Products Synchronization (P21)',
        self::TYPE_CM21 => 'MCM Products Synchronization (CM21)',
    ];

    /**
     * @var array
     */
    protected static $_syncConfigPath = [
        self::TYPE_VL01 => MciConfigHelper::XML_PATH_ENABLE_SYNC_VALUES_LISTS,
        self::TYPE_H01  => MciConfigHelper::XML_PATH_ENABLE_SYNC_HIERARCHIES,
        self::TYPE_PM01 => MciConfigHelper::XML_PATH_ENABLE_SYNC_ATTRIBUTES,
        self::TYPE_CA01 => CatalogConfigHelper::XML_PATH_ENABLE_SYNC_CATEGORIES,
        self::TYPE_P21  => CatalogConfigHelper::XML_PATH_ENABLE_SYNC_PRODUCTS,
        self::TYPE_CM21 => McmConfigHelper::XML_PATH_ENABLE_SYNC_MCM_PRODUCTS,
    ];

    /**
     * @var array
     */
    protected static $_actions = [
        self::ACTION_PREPARE => 'prepare',
        self::ACTION_UPDATE  => 'update',
        self::ACTION_DELETE  => 'delete',
    ];

    /**
     * Init resource model and id field
     *
     * @return  void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Event::class);
    }

    /**
     * @return  array
     */
    public static function getActions()
    {
        return self::$_actions;
    }

    /**
     * @return  array
     */
    public function getCsvData()
    {
        $data = $this->_getData('csv_data');
        if (is_string($data)) {
            $data = unserialize($data);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param   null|string
     * @return  array|string
     */
    public static function getStatuses()
    {
        static $statuses;
        if (!$statuses) {
            $class = new \ReflectionClass(__CLASS__);
            foreach ($class->getConstants() as $name => $value) {
                if (0 === strpos($name, 'STATUS_')) {
                    $statuses[$value] = $value;
                }
            }
        }

        return $statuses;
    }

    /**
     * @return  string
     */
    public function getStatusClass()
    {
        switch ($this->getStatus()) {
            case self::STATUS_WAITING:
                $class = 'grid-severity-minor';
                break;
            case self::STATUS_PROCESSING:
            case self::STATUS_SENT:
                $class = 'grid-severity-major';
                break;
            case self::STATUS_INTERNAL_ERROR:
            case self::STATUS_MIRAKL_ERROR:
                $class = 'grid-severity-critical';
                break;
            case self::STATUS_SUCCESS:
            default:
                $class = 'grid-severity-notice';
        }

        return $class;
    }

    /**
     * @param   int
     * @return  string
     */
    public static function getSyncConfigPath($type)
    {
        return self::$_syncConfigPath[$type];
    }

    /**
     * @param   int
     * @return  string
     */
    public static function getTypeLabel($type)
    {
        return self::$_types[$type];
    }

    /**
     * @return  array
     */
    public static function getTypes()
    {
        return self::$_types;
    }
}
