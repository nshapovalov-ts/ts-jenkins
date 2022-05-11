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

namespace Retailplace\MiraklSeller\Plugin\Amasty\Shopby\Model\Request;

use Retailplace\MiraklSeller\Controller\Index\Index;
use Amasty\Shopby\Model\Request\Builder as RequestBuider;
use Closure;
use Magento\Framework\App\Request\Http;

/**
 * Class Builder
 */
class Builder
{
    /**
     * @var Http
     */
    protected $httpRequest;

    /**
     * Builder constructor.
     * @param Http $http
     */
    public function __construct(
        Http $http
    ) {
        $this->httpRequest = $http;
    }

    /**
     * @param RequestBuider $subject
     * @param Closure $proceed
     * @param $placeholder
     * @param $value
     * @return array
     */
    public function aroundMakeCategoryPlaceholder(
        RequestBuider $subject,
        Closure $proceed,
        $placeholder,
        $value
    ): array {
        $routeName = $this->httpRequest->getRouteName();

        if (!empty($value) && $this->httpRequest->getParam('cat') && in_array($routeName, [
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
            return !is_array($value) ? [$value] : $value;
        }

        $value = $proceed($placeholder, $value);
        return !is_array($value) ? [$value] : $value;
    }
}
