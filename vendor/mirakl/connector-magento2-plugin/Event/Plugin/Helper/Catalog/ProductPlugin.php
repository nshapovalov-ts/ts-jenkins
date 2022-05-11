<?php
namespace Mirakl\Event\Plugin\Helper\Catalog;

use Mirakl\Event\Model\Event;
use Mirakl\Event\Plugin\Helper\AbstractHelperPlugin;

class ProductPlugin extends AbstractHelperPlugin
{
    /**
     * {@inheritdoc}
     */
    protected function addToEventQueue(array $data)
    {
        $this->addEvent($data['product-sku'], $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEventType()
    {
        return Event::TYPE_P21;
    }
}
