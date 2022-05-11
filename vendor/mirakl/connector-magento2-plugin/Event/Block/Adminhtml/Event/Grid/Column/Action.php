<?php
namespace Mirakl\Event\Block\Adminhtml\Event\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column;
use Mirakl\Event\Model\Event;

class Action extends Column
{
    /**
     * @return  array
     */
    public function getOptions()
    {
        $options = [];
        foreach (Event::getActions() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => (string) __($label),
            ];
        }

        return $options;
    }
}
