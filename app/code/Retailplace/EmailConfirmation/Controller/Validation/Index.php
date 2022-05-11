<?php

/**
 * Retailplace_EmailConfirmation
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\EmailConfirmation\Controller\Validation;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 */
class Index extends Action implements HttpGetActionInterface
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    private $resultPageFactory;

    /** @var \Magento\Customer\Model\Session */
    private $customerSession;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        PageFactory $pageFactory,
        Context $context,
        Session $customerSession
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $pageFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $result = $this->resultRedirectFactory->create();
            $result->setPath($this->_url->getBaseUrl());
        } else {
            $email = $this->getRequest()->getParam('email');
            if ($email) {
                $result = $this->resultPageFactory->create();
                $result->getConfig()->getTitle()->set(__('Email Confirmation'));
            } else {
                $result = $this->resultRedirectFactory->create();
                $result->setPath('customer/account/create');
            }
        }

        return $result;
    }
}
