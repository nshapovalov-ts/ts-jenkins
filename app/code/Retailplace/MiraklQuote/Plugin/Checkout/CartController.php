<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Plugin\Checkout;

use Magento\Checkout\Controller\Cart\Index;
use Magento\Framework\Controller\Result\ForwardFactory;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;

/**
 * Class CartController
 */
class CartController
{
    /** @var \Magento\Framework\Controller\Result\ForwardFactory */
    private $forwardFactory;

    /**
     * @param \Magento\Framework\Controller\Result\ForwardFactory $forwardFactory
     */
    public function __construct(ForwardFactory $forwardFactory)
    {
        $this->forwardFactory = $forwardFactory;
    }

    /**
     * Disable Cart page if we have Mirakl Quote ID param
     *
     * @param \Magento\Checkout\Controller\Cart\Index $controller
     */
    public function beforeExecute(Index $controller)
    {
        if ($controller->getRequest()->getParam(MiraklQuoteManagement::REQUEST_PARAM_MIRAKL_QUOTE_ID)) {
            $forward = $this->forwardFactory->create();
            $forward->forward('noroute');
        }
    }
}
