<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model\Config\Source;

/**
 * Class DaysOfWeek source model
 */
class DaysOfWeek implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [[
            'value' => 'all',
            'label' => __('All Days'),
        ]];

        $timestamp = strtotime('next Sunday');
        for ($i = 0; $i < 7; $i++) {
            $options[] = [
                'value' => date('N', $timestamp),
                'label' => date('l', $timestamp),
            ];
            $timestamp = strtotime('+1 day', $timestamp);
        }
        return $options;
    }
}
