<?php

/**
 * Retailplace_MiraklSeller
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 * @author      Natalia Sekulich <natalia@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklSeller\Plugin\Amasty\Shopby\Model\Layer\Filter;

use Magento\Framework\App\RequestInterface;
use Closure;
use Retailplace\MiraklSeller\Controller\Index\Index;

/**
 * Class Category
 */
class Category
{
    /**
     * Apply
     * @param \Amasty\Shopby\Model\Layer\Filter\Category $subject
     * @param Closure $proceed
     * @param RequestInterface $request
     * @return \Amasty\Shopby\Model\Layer\Filter\Category|mixed
     */
    public function aroundApply(
        \Amasty\Shopby\Model\Layer\Filter\Category $subject,
        Closure $proceed,
        RequestInterface $request
    ) {
        $routeName = $request->getRouteName();

        if (!$request->getParam('cat') && in_array($routeName, [
                "marketplace",
                "sale",
                "madeinau",
                "clearance",
                "au_post",
                "boutique",
                "seller-specials",
                Index::NEW_SUPPLIERS_PAGE,
                Index::NEW_PRODUCTS_PAGE,
            ])
        ) {
            return $subject;
        }
        return $proceed($request);
    }
}
