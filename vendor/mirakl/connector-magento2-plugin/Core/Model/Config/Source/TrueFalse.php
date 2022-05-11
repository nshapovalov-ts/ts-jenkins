<?php
namespace Mirakl\Core\Model\Config\Source;

class TrueFalse implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'true', 'label' => __('Yes')],
            ['value' => 'false', 'label' => __('No')]
        ];
    }
}
