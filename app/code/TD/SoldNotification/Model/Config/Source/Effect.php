<?php
/**
 * Recent Order Notification
 * @author      Trinh Doan
 * @copyright   Copyright (c) 2017 Trinh Doan
 * @package     TD_SoldNotification
 */

namespace TD\SoldNotification\Model\Config\Source;

class Effect implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'enter: "animated fadeInDown", exit: "animated fadeOutUp"', 'label' => __('fadeInDown')],
            ['value' => 'enter: "animated fadeInUp", exit: "animated fadeOutDown"', 'label' => __('fadeInUp')],
            ['value' => 'enter: "animated bounceInDown", exit: "animated bounceOutUp"', 'label' => __('bounceInDown')],
            ['value' => 'enter: "animated bounceIn", exit: "animated fadeOutDown"', 'label' => __('bounceIn')],
        ];
    }
}