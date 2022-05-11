<?php
namespace Mirakl\Event\Plugin\Helper\Catalog;

use Mirakl\Event\Model\Event;
use Mirakl\Event\Plugin\Helper\AbstractHelperPlugin;

class CategoryPlugin extends AbstractHelperPlugin
{
    /**
     * {@inheritdoc}
     */
    protected function addToEventQueue(array $data)
    {
        // Parent code and code are concatenated to make an unique code
        // manage 1 delete and 1 update when a category is moved
        $code = $data['parent-code'] . '|' . $data['category-code'];
        $this->addEvent($code, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEventType()
    {
        return Event::TYPE_CA01;
    }
}
