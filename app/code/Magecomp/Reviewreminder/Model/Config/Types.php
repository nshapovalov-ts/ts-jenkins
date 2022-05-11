<?php
namespace Magecomp\Reviewreminder\Model\Config;


class Types implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('None')],
            ['value' => 1, 'label' => __('Email')],
            ['value' => 2, 'label' => __('SMS')],
            ['value' => 3, 'label' => __('Email & SMS Both')],
        ];

    }
}