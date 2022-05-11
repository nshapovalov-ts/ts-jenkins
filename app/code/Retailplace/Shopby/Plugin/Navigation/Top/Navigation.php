<?php

/**
 * Retailplace_Shopby
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Kosykh <dmitriykosykh@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Shopby\Plugin\Navigation\Top;

use Amasty\Shopby\Block\Navigation\Top\Navigation as ShopbyNavigation;

class Navigation
{
    /**
     * @param ShopbyNavigation $subject
     * @param array $filters
     *
     * @return array
     */
    public function afterGetFilters(ShopbyNavigation $subject, array $filters): array
    {
        $this->hideFilters($subject, $filters);
        return $filters;
    }

    /**
     * @param ShopbyNavigation $subject
     * @param array $filters
     */
    protected function hideFilters(ShopbyNavigation $subject, array &$filters): void
    {
        if ($showAttributes = $subject->getData('showAttributes')) {
            $showAttributes = explode(',', $showAttributes);
            foreach ($filters as $filterId => $filter) {
                $code = $filter->getRequestVar();
                if (!in_array($code, $showAttributes)) {
                    unset($filters[$filterId]);
                }
            }
        }
    }
}
