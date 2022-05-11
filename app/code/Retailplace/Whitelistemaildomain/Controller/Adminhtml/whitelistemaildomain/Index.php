<?php

namespace Retailplace\Whitelistemaildomain\Controller\Adminhtml\whitelistemaildomain;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Retailplace_Whitelistemaildomain::whitelistemaildomain';

    /**
     * @var PageFactory
     */
    protected $resultPagee;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Retailplace_Whitelistemaildomain::whitelistemaildomain');
        $resultPage->addBreadcrumb(__('Retailplace'), __('Retailplace'));
        $resultPage->addBreadcrumb(__('Manage item'), __('Manage Domain'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Domain'));

        return $resultPage;
    }
    protected function _isAllowed()
    {
      return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
?>