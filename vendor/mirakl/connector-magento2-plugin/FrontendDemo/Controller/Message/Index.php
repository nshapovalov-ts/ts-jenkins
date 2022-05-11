<?php
namespace Mirakl\FrontendDemo\Controller\Message;

class Index extends AbstractMessage
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->isLoggedIn()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('no-route');

            return $resultRedirect;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Messages'));

        $block = $resultPage->getLayout()->getBlock('customer.account.link.back');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }

        return $resultPage;
    }
}
