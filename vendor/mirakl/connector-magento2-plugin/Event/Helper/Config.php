<?php
namespace Mirakl\Event\Helper;

class Config extends \Mirakl\Core\Helper\Config
{
    const XML_PATH_EVENT_ASYNC_ACTIVE = 'mirakl_event/general/event_async_active';

    /**
     * @return  array
     */
    public function getAsyncEvents()
    {
        $types = $this->getValue(self::XML_PATH_EVENT_ASYNC_ACTIVE);

        return explode(',', $types);
    }
}