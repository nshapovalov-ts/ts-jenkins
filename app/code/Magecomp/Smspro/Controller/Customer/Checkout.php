<?php

namespace Magecomp\Smspro\Controller\Customer;

class Checkout extends \Magento\Framework\App\Action\Action
{
    protected $custsession;
    protected $helpercustomer;
    protected $resultRedirectFactory;

    public function __construct( \Magento\Framework\App\Action\Context $context,
                                 \Magento\Customer\Model\Session $custsession,
                                 \Magecomp\Smspro\Helper\Customer $helpercustomer,
                                 \Magento\Framework\Controller\Result\Redirect $resultRedirect )
    {
        $this->custsession = $custsession;
        $this->helpercustomer = $helpercustomer;
        $this->resultRedirectFactory = $resultRedirect;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->custsession->isLoggedIn() && $this->helpercustomer->isOrderConfirmationForUser()) {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Mobile Verification'));
            $this->_view->renderLayout();
        } else {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('');
            return $resultRedirect;
        }
    }
}