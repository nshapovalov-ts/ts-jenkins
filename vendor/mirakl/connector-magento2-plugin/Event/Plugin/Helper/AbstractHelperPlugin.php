<?php
namespace Mirakl\Event\Plugin\Helper;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Mirakl\Connector\Common\ExportInterface;
use Mirakl\Event\Helper\Config as EventConfig;
use Mirakl\Event\Helper\Data as EventHelper;
use Mirakl\Event\Model\Event;

abstract class AbstractHelperPlugin
{
    /**
     * @var EventConfig
     */
    protected $eventConfig;

    /**
     * @var EventHelper
     */
    protected $eventHelper;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @param   EventConfig             $eventConfig
     * @param   EventHelper             $eventHelper
     * @param   EventManagerInterface   $eventManager
     */
    public function __construct(
        EventConfig $eventConfig,
        EventHelper $eventHelper,
        EventManagerInterface $eventManager
    ) {
        $this->eventConfig  = $eventConfig;
        $this->eventHelper  = $eventHelper;
        $this->eventManager = $eventManager;
    }

    /**
     * Creates an event for each data to export
     *
     * @param   array   $eventData
     * @return  void
     */
    abstract protected function addToEventQueue(array $eventData);

    /**
     * Returns the event type constant
     *
     * @return  int
     */
    abstract protected function getEventType();

    /**
     * @param   string  $code
     * @param   array   $data
     * @return  Event
     */
    protected function addEvent($code, array $data)
    {
        if (!isset($data['update-delete'])) {
            throw new \InvalidArgumentException('Action must be defined in "update-delete" column of event data');
        }

        $action = $data['update-delete'] == 'delete'
            ? Event::ACTION_DELETE
            : Event::ACTION_UPDATE;

        return $this->eventHelper->addEvent($code, $action, $this->getEventType(), $data);
    }

    /**
     * @param   ExportInterface $subject
     * @param   \Closure        $proceed
     * @param   DataObject      $object
     * @return  int|null
     */
    public function aroundDelete(ExportInterface $subject, \Closure $proceed, DataObject $object)
    {
        if (!$this->isAsynchronousEnabled()) {
            return $proceed($object);
        }

        if ($subject->isExportable()) {
            $this->addToEventQueue($subject->prepare($object, 'delete'));
        }
    }

    /**
     * @param   ExportInterface $subject
     * @param   \Closure        $proceed
     * @param   DataObject      $object
     * @return  int|null
     */
    public function aroundUpdate(ExportInterface $subject, \Closure $proceed, DataObject $object)
    {
        if (!$this->isAsynchronousEnabled()) {
            return $proceed($object);
        }

        if ($subject->isExportable()) {
            $this->addToEventQueue($subject->prepare($object));
        }
    }

    /**
     * Checks if this export must be done in asynchronous or synchronous mode according to config
     *
     * @return  bool
     */
    protected function isAsynchronousEnabled()
    {
        $types = $this->eventConfig->getAsyncEvents();

        return in_array($this->getEventType(), $types);
    }
}