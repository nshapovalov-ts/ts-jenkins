<?php

/**
 * Retailplace_Search
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Search\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Level
 */
class Level implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'info', 'label' => __('Info')],
            ['value' => 'warn', 'label' => __('Warn')],
            ['value' => 'debug', 'label' => __('Debug')],
            ['value' => 'trace', 'label' => __('Trace')]
        ];
    }
}
