<?php
namespace Magecomp\Smspro\Model\Config;


class Countryflagset implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('By IP')],
            ['value' => 1, 'label' => __('Selected Country')]
        ];

    }
}