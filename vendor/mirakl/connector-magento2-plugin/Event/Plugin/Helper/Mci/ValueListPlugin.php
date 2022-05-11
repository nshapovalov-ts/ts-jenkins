<?php
namespace Mirakl\Event\Plugin\Helper\Mci;

use Mirakl\Event\Model\Event;
use Mirakl\Event\Plugin\Helper\AbstractHelperPlugin;

class ValueListPlugin extends AbstractHelperPlugin
{
    /**
     * {@inheritdoc}
     */
    protected function addToEventQueue(array $data)
    {
        foreach ($data as $eventData) {
            // Attribute code and value list code are concatenated to make an unique code
            $code = $eventData['list-code'] . '|' . $eventData['value-code'];
            $this->addEvent($code, $eventData);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEventType()
    {
        return Event::TYPE_VL01;
    }
}
