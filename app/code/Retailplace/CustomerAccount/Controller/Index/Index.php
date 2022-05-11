<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Retailplace\CustomerAccount\Controller\Index;


use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        UrlInterface $url,
        ResultFactory $resultFactory,
        PageFactory $resultPageFactory
    ) {
        $this->session = $customerSession;
        $this->url = $url;
        $this->resultFactory = $resultFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->session->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setUrl($this->url->getBaseUrl());
            return $result;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
