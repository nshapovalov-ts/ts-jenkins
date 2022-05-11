<?php

/**
 * Retailplace_AjaxCart
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AjaxCart\Plugin;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\App\ActionInterface;
use \Magento\Framework\App\Request\Http;

/**
 * Class AjaxAction
 */
class AjaxAction
{
    /** @var \Magento\Framework\App\Request\Http */
    private $http;

    /**
     * @param \Magento\Framework\App\Request\Http $http
     */
    public function __construct(
        Http $http
    ) {
        $this->http = $http;
    }

    /**
     * Remove redirect for ajax requests
     *
     * @param \Magento\Framework\App\ActionInterface $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     * @return mixed
     */
    public function afterExecute(ActionInterface $subject, Redirect $result)
    {
        if ($this->http->isAjax()) {
            $result = null;
        }

        return $result;
    }
}
