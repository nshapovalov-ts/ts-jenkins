<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Model\Config\Source;

/**
 * Class Time source model
 */
class Time implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [[
            'value' => 0,
            'label' => __('Please select'),
        ]];

        $time = strtotime(date('Y-m-d 00:00:00'));
        for ($i = 0; $i < 86400; $i+=900) {
            $options[] = [
                'value' => $i ?: 86400,
                'label' => date('h:i A', $time + $i),
            ];
        }
        return $options;
    }
}
