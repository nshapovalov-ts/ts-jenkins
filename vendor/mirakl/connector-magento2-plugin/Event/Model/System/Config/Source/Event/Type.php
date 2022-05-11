<?php
namespace Mirakl\Event\Model\System\Config\Source\Event;

use Mirakl\Event\Model\Event;

class Type
{
    /**
     * Retrieves event types
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (Event::getTypes() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => __($label),
            ];
        }

        return $options;
    }
}
