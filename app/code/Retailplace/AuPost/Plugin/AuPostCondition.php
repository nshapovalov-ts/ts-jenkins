<?php

/**
 * Retailplace_AuPost
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AuPost\Plugin;

use Magento\SalesRule\Model\Rule\Condition\Product\Combine;
use Retailplace\AuPost\Model\Rule\Condition\AuPost;

/**
 * Class AuPostCondition
 *
 * @see \Magento\SalesRule\Model\Rule\Condition\Product\Combine
 */
class AuPostCondition
{
    /**
     * Add custom Condition to Cart Price Rules to Cart Items section
     *
     * @param \Magento\SalesRule\Model\Rule\Condition\Product\Combine $subject
     * @param array $result
     * @return array
     */
    public function afterGetNewChildSelectOptions(Combine $subject, array $result): array
    {
        foreach ($result as &$condition) {
            if ((string) $condition['label'] == 'Cart Item Attribute') {
                $condition['value'][] = $this->getCondition();
                break;
            }
        }

        return $result;
    }

    /**
     * Get condition
     *
     * @return array
     */
    protected function getCondition(): array
    {
        return [
            'value'=> AuPost::class,
            'label'=> __('Is AU Post Product')
        ];
    }
}
