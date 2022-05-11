<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class CategoriesWidgetLayoutType implements ArrayInterface
{
    const LAYOUT_1_COLUMN = 'am-widget-categories-1';
    const LAYOUT_2_COLUMN = 'am-widget-categories-2';
    const LAYOUT_3_COLUMN = 'am-widget-categories-3';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::LAYOUT_1_COLUMN, 'label' => __('1 column')],
            ['value' => self::LAYOUT_2_COLUMN, 'label' => __('2 columns')],
            ['value' => self::LAYOUT_3_COLUMN, 'label' => __('3 columns')]
        ];
    }
}
