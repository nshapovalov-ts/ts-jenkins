<?php

/**
 * Retailplace_Reorder
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Reorder\Controller\Index;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Review\Controller\Customer as CustomerController;

/**
 * Index class
 */
class Index extends CustomerController implements OrderInterface, HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
        public function __construct(
            Context $context,
            Session $customerSession,
            PageFactory $resultPageFactory
        ) {
            $this->resultPageFactory = $resultPageFactory;
            parent::__construct($context, $customerSession);
        }


    /**
     * Customer reorder page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Reorder'));

        $block = $resultPage->getLayout()->getBlock('customer.account.link.back');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        return $resultPage;
    }
}
