<?php

namespace Magecomp\Smspro\Controller\Adminhtml\Phonebook;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magecomp_Smspro::magecompsms');
        $resultPage->addBreadcrumb(__('Magecomp Smspro'), __('Phonebook'));
        $resultPage->getConfig()->getTitle()->prepend(__('Phonebook'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return true;
    }
}