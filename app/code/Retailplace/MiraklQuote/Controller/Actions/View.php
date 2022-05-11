<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Controller\Actions;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Router\Base;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Controller\AbstractAccount as CustomerController;

/**
 * Class View
 */
class View extends CustomerController implements HttpGetActionInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $result->getConfig()->getTitle()->set(__('View Quote'));

        if (!$this->getRequest()->getParam('id')) {
            $this->_forward(Base::NO_ROUTE);
        }

        return $result;
    }
}
