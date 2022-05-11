<?php
namespace Mirakl\Event\Plugin\Helper\Mcm\Product\Export;

use Mirakl\MCM\Front\Domain\Product\Synchronization\ProductAcceptance;
use Mirakl\Mcm\Helper\Product\Export\Process as ProcessHelper;
use Mirakl\Event\Helper\Config as EventConfig;
use Mirakl\Event\Helper\Data as EventHelper;
use Mirakl\Event\Model\Event;

class ProcessPlugin
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
     * @param   EventConfig $eventConfig
     * @param   EventHelper $eventHelper
     */
    public function __construct(EventConfig $eventConfig, EventHelper $eventHelper)
    {
        $this->eventConfig = $eventConfig;
        $this->eventHelper = $eventHelper;
    }

    /**
     * @param   string  $code
     * @param   string  $action
     * @param   array   $data
     * @return  Event
     */
    public function addEvent($code, $action, array $data)
    {
        return $this->eventHelper->addEvent($code, $action, $this->getEventType(), $data);
    }

    /**
     * @param   ProcessHelper   $subject
     * @param   \Closure        $proceed
     * @param   int             $productId
     * @param   string          $acceptance
     * @return  int|false|null
     */
    public function aroundExportProduct(
        ProcessHelper $subject,
        \Closure $proceed,
        $productId,
        $acceptance = ProductAcceptance::STATUS_ACCEPTED
    ) {
        if (!$this->isAsynchronousEnabled()) {
            return $proceed($productId, $acceptance);
        }

        $action = $acceptance === ProductAcceptance::STATUS_ACCEPTED
            ? Event::ACTION_UPDATE
            : Event::ACTION_DELETE;

        $this->addEvent($productId, $action, $subject->prepareProductFromId($productId, $acceptance));
    }

    /**
     * Returns the event type constant
     *
     * @return  int
     */
    protected function getEventType()
    {
        return Event::TYPE_CM21;
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
