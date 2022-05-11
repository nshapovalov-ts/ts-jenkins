<?php
namespace Mirakl\Event\Block\Adminhtml\Event\Grid\Column;

use Mirakl\Event\Model\Event;
use Magento\Backend\Block\Widget\Grid\Column;

class Type extends Column
{
    /**
     * @return  array
     */
    public function getOptions()
    {
        $options = [];
        foreach (Event::getTypes() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => (string) __($label),
            ];
        }

        return $options;
    }
}
