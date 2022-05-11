<?php
/**
 * Created by PhpStorm.
 * User: Bharat-Magecomp
 * Date: 8/26/2019
 * Time: 11:34 AM
 */

namespace Magecomp\Smsmsg91\Model\Config;


class Router implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 4, 'label' => __('Transactional')],
            ['value' => 1, 'label' => __('Promotional')]
        ];

    }
}