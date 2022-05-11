<?php
namespace Magecomp\Smsmsgclub\Model\Config;

class Contenttype implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => "english", 'label' => __('English')],
            ['value' => "unicode", 'label' => __('Unicode')],
        ];
    }
}