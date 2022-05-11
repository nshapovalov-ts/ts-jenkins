<?php
namespace Mirakl\Event\Plugin\Helper\Mci;

use Mirakl\Event\Model\Event;
use Mirakl\Event\Plugin\Helper\AbstractHelperPlugin;

class HierarchyPlugin extends AbstractHelperPlugin
{
    /**
     * {@inheritdoc}
     */
    protected function addToEventQueue(array $data)
    {
        // Parent code and code are concatenated to make an unique code and
        // manage 1 delete and 1 update when a category is moved
        $code = $data['hierarchy-parent-code'] . '|' . $data['hierarchy-code'];
        $this->addEvent($code, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEventType()
    {
        return Event::TYPE_H01;
    }
}
