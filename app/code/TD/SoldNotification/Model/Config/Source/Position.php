<?php
/**
 * Recent Order Notification
 * @author      Trinh Doan
 * @copyright   Copyright (c) 2017 Trinh Doan
 * @package     TD_SoldNotification
 */

namespace TD\SoldNotification\Model\Config\Source;

class Position implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'from: "top", align: "left"', 'label' => __('Top Left')],
            ['value' => 'from: "top", align: "center"', 'label' => __('Top Center')],
            ['value' => 'from: "top", align: "right"', 'label' => __('Top Right')],
            ['value' => 'from: "bottom", align: "left"', 'label' => __('Bottom Left')],
            ['value' => 'from: "bottom", align: "center"', 'label' => __('Bottom Center')],
            ['value' => 'from: "bottom", align: "right"', 'label' => __('Bottom Right')],
        ];
    }
}