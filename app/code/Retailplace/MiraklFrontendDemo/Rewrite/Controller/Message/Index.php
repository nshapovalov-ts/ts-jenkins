<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Rewrite\Controller\Message;

use Magento\Framework\View\Result\Page;
use Mirakl\FrontendDemo\Controller\Message\Index as MessageIndex;
use Magento\Framework\Controller\Result\Redirect;

class Index extends MessageIndex
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        if (!$this->customerSession->isLoggedIn()) {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $currentUrl = $this->_url->getCurrentUrl();
            $resultRedirect->setPath('customer/account/login', ['referer' => base64_encode($currentUrl)]);
            return $resultRedirect;
        }

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Messages'));

        $block = $resultPage->getLayout()->getBlock('customer.account.link.back');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }

        return $resultPage;
    }
}
